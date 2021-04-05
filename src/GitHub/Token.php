<?php namespace Winter\Cli\GitHub;

use Exception;
use Vtiful\Kernel\Excel;
use Winter\Cli\Filesystem\DataDir;

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

    /** @var string GitHub token filename */
    protected $tokenFile = 'github_token';

    /**
     * Writes the token to a suitable available location.
     *
     * @param string $token
     * @return string Path to the token file.
     * @throws Exception If the token file cannot be written
     */
    public function write(string $token): string
    {
        try {
            $dataDir = new DataDir();
            $written = $dataDir->put($this->tokenFile, $token);
        } catch (Exception $e) {
            throw new Exception('Unable to store the GitHub Access Token.');
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

        $dataDir = new DataDir();
        $token = $dataDir->exists($this->tokenFile);

        if (!$token) {
            throw new Exception(
                'You must provide a GitHub Access Token for Winter CLI Helper to use this feature.'
                . ' Please visit' . "\n"
                . 'https://github.com/settings/tokens/new?scopes=public_repo&description=Winter%20CMS%20CLI%20Helper'
                . ' to create a token, then use the "github:token" command to store the token for further use.'
            );
        }

        return $dataDir->get($this->tokenFile);
    }
}
