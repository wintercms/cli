<?php namespace Winter\Cli\Commands\ProjectAlerts;

use Winter\Cli\BaseCommand;
use Winter\Cli\Commands\ProjectVersion\FileManifest;
use Winter\Cli\Commands\ProjectVersion\SourceManifest;
use Winter\Cli\GitHub\Token;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Security alerts command
 *
 * @since 0.2.1
 * @author Ben Thomson
 */
class Command extends BaseCommand
{
    /**
     * @inheritDoc
     */
    protected static $defaultName = 'project:alerts';

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this
            // the short description shown while running "php bin/console list"
            ->setDescription('View security alerts for an Winter CMS project.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Determines if a particular Winter CMS project has any published security advisories from the Winter'
                . ' CMS security advisory database at https://github.com/wintercms/winter/security/advisories/.'
            )

            // arguments
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The path to the Winter CMS project.'
            )
        ;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Get build
        $manifest = new FileManifest($input->getArgument('path'));

        $this->comment('Checking advisory database...', OutputInterface::VERBOSITY_VERBOSE);

        $source = new SourceManifest();
        $build = $source->compare($manifest);
        $version = $build['build'];

        // Convert version to semantic versioning, if necessary
        if (intval($version) === $version) {
            $version = '1.0.' . $version;
        }

        // Query security advisories for all Winter packages
        $advisories = $this->getAdvisories($version);

        if (!count($advisories)) {
            $this->success(
                'Your version of Winter CMS (' . $version . ') has no known security advisories.',
                OutputInterface::VERBOSITY_QUIET
            );

            return 0;
        }

        $this->error(
            'Your version of Winter CMS has ' .
            (
                (count($advisories) === 1)
                    ? '1 known security advisory'
                    : count($advisories) . ' known security advisories'
            )
            . ':',
            OutputInterface::VERBOSITY_QUIET
        );

        $minReqVersion = null;

        foreach ($advisories as $advisory) {
            $this->line('');

            switch ($advisory['severity']) {
                case 'HIGH':
                    $this->danger('High severity');
                    break;
                case 'MODERATE':
                    $this->warn('Moderate severity');
                    break;
                case 'LOW':
                    $this->info('Low severity');
                    break;
            }

            $this->line('   <bold>' . $advisory['summary'] . '</bold>');
            $this->line('   ' . $advisory['link']);

            if (is_null($minReqVersion) || version_compare($advisory['fixed'], $minReqVersion, '>')) {
                $minReqVersion = $advisory['fixed'];
            }
        }

        $this->line('', OutputInterface::VERBOSITY_QUIET);
        $this->error(
            'You should upgrade to at least Winter CMS ' . $minReqVersion . ' as soon as possible.',
            OutputInterface::VERBOSITY_QUIET
        );

        return 1;
    }

    /**
     * Determines security advisories for a given version.
     *
     * @param string $version
     * @return array
     */
    protected function getAdvisories(string $version): array
    {
        $advisories = [];
        $github = new \Github\Client();
        $token = (new Token())->read();

        $github->authenticate($token, null, \Github\Client::AUTH_ACCESS_TOKEN);

        foreach (['storm', 'wn-backend-module', 'wn-cms-module', 'wn-system-module'] as $package) {
            $sourceAdvisories = $github->api('graphql')->execute('{
                securityVulnerabilities(ecosystem: COMPOSER, package: "winter/' . $package . '", first: 100) {
                    edges {
                        node {
                            advisory {
                                id
                                summary
                                permalink
                                ghsaId
                                withdrawnAt
                            }
                            vulnerableVersionRange
                            severity
                            firstPatchedVersion {
                                identifier
                            }
                        }
                    }
                }
            }');

            if (!isset($sourceAdvisories['data']['securityVulnerabilities']['edges'])) {
                continue;
            }

            foreach ($sourceAdvisories['data']['securityVulnerabilities']['edges'] as $edge) {
                if (version_compare($version, $edge['node']['firstPatchedVersion']['identifier'], '>=')) {
                    continue;
                }
                if (!is_null($edge['node']['advisory']['withdrawnAt'])) {
                    continue;
                }

                $advisories[] = [
                    'id' => $edge['node']['advisory']['ghsaId'] ?? 'Unknown',
                    'summary' => $edge['node']['advisory']['summary'],
                    'severity' => $edge['node']['severity'],
                    'link' => $edge['node']['advisory']['permalink'],
                    'fixed' => $edge['node']['firstPatchedVersion']['identifier']
                ];
            }
        }

        return $advisories;
    }
}
