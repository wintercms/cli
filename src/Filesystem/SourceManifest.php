<?php namespace Winter\Cli\Filesystem;

use Exception;

/**
 * Reads and stores the Winter CMS source manifest information.
 *
 * The source manifest is a meta JSON file, stored on GitHub, that contains the hashsums of all module files across all
 * buils of Winter CMS. This allows us to compare the Winter CMS installation against the expected file checksums and
 * determine the installed build and whether it has been modified.
 *
 * Based off the following implementation, but decoupled for this CLI helpers' use.
 * https://github.com/wintercms/winter/blob/develop/modules/system/classes/SourceManifest.php
 *
 * @since 0.1.0
 * @author Ben Thomson
 */
class SourceManifest
{
    /**
     * @var string The URL to the source manifest
     */
    protected $source = 'https://raw.githubusercontent.com/wintercms/meta/master/manifest/builds.json';

    /**
     * @var array Array of builds, keyed by build number, with files for keys and hashes for values.
     */
    protected $builds = [];

    /**
     * @var array The version map where forks occurred.
     */
    protected $forks;

    /**
     * @var string The URL to the forked version manifest
     */
    protected $forksUrl = 'https://raw.githubusercontent.com/wintercms/meta/master/manifest/forks.json';

    /**
     * Constructor
     *
     * @param string $manifest Manifest file to load
     * @param string $forks Forks manifest file to load
     * @param bool $autoload Loads the manifest on construct
     */
    public function __construct($source = null, $forks = null, $autoload = true)
    {
        if (isset($source)) {
            $this->setSource($source);
        }

        if (isset($forks)) {
            $this->setForksSource($forks);
        }

        if ($autoload) {
            $this->loadSource();
            $this->loadForks();
        }
    }

    /**
     * Sets the source manifest URL.
     *
     * @param string $source
     * @return void
     */
    public function setSource($source)
    {
        if (is_string($source)) {
            $this->source = $source;
        }
    }

    /**
     * Sets the forked version manifest URL.
     *
     * @param string $forks
     * @return void
     */
    public function setForksSource($forks)
    {
        if (is_string($forks)) {
            $this->forksUrl = $forks;
        }
    }

    /**
     * Loads the manifest file.
     *
     * @throws Exception If the manifest is invalid, or cannot be parsed.
     */
    public function loadSource()
    {
        $source = file_get_contents($this->source);
        if (empty($source)) {
            throw new Exception(
                'Source manifest not found'
            );
        }

        $data = json_decode($source, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                'Unable to decode source manifest JSON data. JSON Error: ' . json_last_error_msg()
            );
        }
        if (!isset($data['manifest']) || !is_array($data['manifest'])) {
            throw new Exception(
                'The source manifest at "' . $this->source . '" does not appear to be a valid source manifest file.'
            );
        }

        foreach ($data['manifest'] as $build) {
            $this->builds[$this->getVersionInt($build['build'])] = [
                'version' => $build['build'],
                'parent' => $build['parent'],
                'modules' => $build['modules'],
                'files' => $build['files'],
            ];
        }

        return $this;
    }

    /**
     * Loads the forked version manifest file.
     *
     * @throws Exception If the manifest is invalid, or cannot be parsed.
     */
    public function loadForks()
    {
        $forks = file_get_contents($this->forksUrl);
        if (empty($forks)) {
            throw new Exception(
                'Forked version manifest not found'
            );
        }

        $data = json_decode($forks, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                'Unable to decode forked version manifest JSON data. JSON Error: ' . json_last_error_msg()
            );
        }
        if (!isset($data['forks']) || !is_array($data['forks'])) {
            throw new Exception(
                'The forked version manifest at "' . $this->forksUrl . '" does not appear to be a valid forked version
                manifest file.'
            );
        }

        // Map forks to int values
        foreach ($data['forks'] as $child => $parent) {
            $this->forks[$this->getVersionInt($child)] = $this->getVersionInt($parent);
        }

        return $this;
    }

    /**
     * Gets all builds.
     *
     * @return array
     */
    public function getBuilds()
    {
        return array_values(array_map(function ($build) {
            return $build['version'];
        }, $this->builds));
    }

    /**
     * Gets the filelist state at a selected build.
     *
     * This method will list all expected files and hashsums at the specified build number. It does this by following
     * the history, switching branches as necessary.
     *
     * @param string|integer $build Build version to get the filelist state for.
     * @throws Exception If the specified build has not been added to the source manifest.
     * @return array
     */
    public function getState($build)
    {
        if (is_string($build)) {
            $build = $this->getVersionInt($build);
        }

        if (!isset($this->builds[$build])) {
            throw new \Exception('The specified build has not been added.');
        }

        $state = [];

        foreach ($this->builds as $number => $details) {
            // Follow fork if necessary
            if (isset($this->forks) && array_key_exists($build, $this->forks)) {
                $state = $this->getState($this->forks[$build]);
            }

            if (isset($details['files']['added'])) {
                foreach ($details['files']['added'] as $filename => $sum) {
                    $state[$filename] = $sum;
                }
            }
            if (isset($details['files']['modified'])) {
                foreach ($details['files']['modified'] as $filename => $sum) {
                    $state[$filename] = $sum;
                }
            }
            if (isset($details['files']['removed'])) {
                foreach ($details['files']['removed'] as $filename) {
                    unset($state[$filename]);
                }
            }

            if ($number === $build) {
                break;
            }
        }

        return $state;
    }

    /**
     * Compares a file manifest with the source manifest.
     *
     * This will determine the build of the Winter CMS installation.
     *
     * This will return an array with the following information:
     *  - `build`: The build number we determined was most likely the build installed.
     *  - `modified`: Whether we detected any modifications between the installed build and the manifest.
     *  - `confident`: Whether we are at least 60% sure that this is the installed build. More modifications to
     *                  to the code = less confidence.
     *  - `changes`: If $detailed is true, this will include the list of files modified, created and deleted.
     *
     * @param FileManifest $manifest The file manifest to compare against the source.
     * @param bool $detailed If true, the list of files modified, added and deleted will be included in the result.
     * @return array
     */
    public function compare(FileManifest $manifest, $detailed = false)
    {
        $modules = $manifest->getModuleChecksums();

        // Look for an unmodified version
        foreach ($this->getBuilds() as $buildString) {
            $build = $this->builds[$this->getVersionInt($buildString)];

            $matched = array_intersect_assoc($build['modules'], $modules);

            if (count($matched) === count($modules)) {
                $details = [
                    'build' => $buildString,
                    'modified' => false,
                    'confident' => true,
                ];

                if ($detailed) {
                    $details['changes'] = [];
                }

                return $details;
            }
        }

        // If we could not find an unmodified version, try to find the closest version and assume this is a modified
        // install.
        $buildMatch = [];

        foreach ($this->getBuilds() as $buildString) {
            $build = $this->builds[$this->getVersionInt($buildString)];

            $state = $this->getState($buildString);

            // Include only the files that match the modules being loaded in this file manifest
            $availableModules = array_keys($modules);

            foreach ($state as $file => $sum) {
                // Determine module
                $module = explode('/', $file)[2];

                if (!in_array($module, $availableModules)) {
                    unset($state[$file]);
                }
            }

            $filesExpected = count($state);
            $filesFound = [];
            $filesChanged = [];

            foreach ($manifest->getFiles() as $file => $sum) {
                // Unknown new file
                if (!isset($state[$file])) {
                    $filesChanged[] = $file;
                    continue;
                }

                // Modified file
                if ($state[$file] !== $sum) {
                    $filesFound[] = $file;
                    $filesChanged[] = $file;
                    continue;
                }

                // Pristine file
                $filesFound[] = $file;
            }

            $foundPercent = count($filesFound) / $filesExpected;
            $changedPercent = count($filesChanged) / $filesExpected;

            $score = ((1 * $foundPercent) - $changedPercent);
            $buildMatch[$buildString] = round($score * 100, 2);
        }

        // Find likely version
        $likelyBuild = array_search(max($buildMatch), $buildMatch);

        $details = [
            'build' => $likelyBuild,
            'modified' => true,
            'confident' => ($buildMatch[$likelyBuild] >= 60)
        ];

        if ($detailed) {
            $details['changes'] = $this->processChanges($manifest, $likelyBuild);
        }

        return $details;
    }

    /**
     * Determines file changes between the specified build and the previous build.
     *
     * Will return an array of added, modified and removed files.
     *
     * @param FileManifest $manifest The current build's file manifest.
     * @param FileManifest|string|integer $previous Either a previous manifest, or the previous build number as an int
     *  or string, used to determine changes with this build.
     * @return array
     */
    protected function processChanges(FileManifest $manifest, $previous = null)
    {
        // If no previous build has been provided, all files are added
        if (is_null($previous)) {
            return [
                'added' => $manifest->getFiles(),
            ];
        }

        // Only save files if they are changing the "state" of the manifest (ie. the file is modified, added or removed)
        if (is_int($previous) || is_string($previous)) {
            $state = $this->getState($previous);
        } else {
            $state = $previous->getFiles();
        }
        $added = [];
        $modified = [];

        foreach ($manifest->getFiles() as $file => $sum) {
            if (!isset($state[$file])) {
                $added[$file] = $sum;
                continue;
            } else {
                if ($state[$file] !== $sum) {
                    $modified[$file] = $sum;
                }
                unset($state[$file]);
            }
        }

        // Any files still left in state have been removed
        $removed = array_keys($state);

        $changes = [];
        if (count($added)) {
            $changes['added'] = $added;
        }
        if (count($modified)) {
            $changes['modified'] = $modified;
        }
        if (count($removed)) {
            $changes['removed'] = $removed;
        }

        return $changes;
    }


    /**
     * Converts a version string into an integer for comparison.
     *
     * @param string $version
     * @throws Exception if a version string does not match the format "major.minor.path"
     * @return int
     */
    protected function getVersionInt(string $version)
    {
        // Get major.minor.patch versions
        if (!preg_match('/^([0-9]+)\.([0-9]+)\.([0-9]+)/', $version, $versionParts)) {
            throw new Exception('Invalid version string - must be of the format "major.minor.path"');
        }

        $int = $versionParts[1] * 1000000;
        $int += $versionParts[2] * 1000;
        $int += $versionParts[3];

        return $int;
    }
}
