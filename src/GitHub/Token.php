<?php namespace Winter\Cli\GitHub;

use Exception;

/**
 * Simple token holder.
 *
 * Stores a GitHub access token in your home directory. The token will retrieve the following permissions:
 *  - repo:public_repo (Access public repositories)
 *
 * @since 0.2.1
 * @author Ben Thomson
 */
class Token
{
    /** @var string stored token */
    protected $token = null;

    /** @var array suitable locations to find and store the token */
    protected $suitableLocations = [];

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->suitableLocations = [
            $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.config',
            $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.local' . DIRECTORY_SEPARATOR . 'share',
            $_SERVER['HOME'] . DIRECTORY_SEPARATOR . 'AppData' . DIRECTORY_SEPARATOR . 'Local',
            $_SERVER['HOME'] . DIRECTORY_SEPARATOR . '.winter-cli',
        ];
    }

    /**
     * Writes the token to an available suitable location.
     *
     * @param string $token
     * @return string Path to the token file.
     * @throws Exception If a suitable location cannot be found.
     */
    public function write(string $token): string
    {
        $written = null;

        foreach ($this->suitableLocations as $dir) {
            if (is_dir($dir)) {
                if (!is_writable($dir)) {
                    continue;
                }
                if (!is_dir($dir . DIRECTORY_SEPARATOR . 'winter-cli')) {
                    mkdir($dir . DIRECTORY_SEPARATOR . 'winter-cli');
                }

                $path = $dir . DIRECTORY_SEPARATOR . 'winter-cli' . DIRECTORY_SEPARATOR . 'token';

                file_put_contents($path, $token);
                $written = $path;
                break;
            }
        }

        if (!$written) {
            throw new Exception('Unable to write the GitHub Access Token in your home directory.');
        }

        return $written;
    }

    /**
     * Get the stored token.
     *
     * @return string
     * @throws Exception If no stored token is found.
     */
    public function read(): string
    {
        if (!is_null($this->token)) {
            return $this->token;
        }

        foreach ($this->suitableLocations as $dir) {
            $path = $dir . DIRECTORY_SEPARATOR . 'winter-cli' . DIRECTORY_SEPARATOR . 'token';

            if (file_exists($path)) {
                $this->token = file_get_contents($path);
                break;
            }
        }

        if ($this->token === null) {
            throw new Exception(
                'You must provide a GitHub Access Token for Winter CLI Helper to use this feature.'
                . ' Please visit' . "\n"
                . 'https://github.com/settings/tokens/new?scopes=public_repo&description=Winter%20CMS%20CLI%20Helper'
                . ' to create a token, then use the "github:token" command to store the token for further use.'
            );
        }

        return $this->token;
    }
}
