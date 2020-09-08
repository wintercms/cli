<?php namespace BennoThommo\OctoberCli\Commands\ProjectAlerts;

use BennoThommo\OctoberCli\BaseCommand;
use BennoThommo\OctoberCli\Commands\ProjectVersion\FileManifest;
use BennoThommo\OctoberCli\Commands\ProjectVersion\SourceManifest;
use BennoThommo\OctoberCli\GitHub\Token;
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
            ->setDescription('View security alerts for an October CMS project.')

            // the full command description shown when running the command with
            // the "--help" option
            ->setHelp(
                'Determines if a particular October CMS project has any published security advisories from the October'
                . ' CMS security advisory database at https://github.com/octobercms/october/security/advisories/.'
            )

            // arguments
            ->addArgument(
                'path',
                InputArgument::OPTIONAL,
                'The path to the October CMS project.'
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

        $this->comment('Checking advisory database...');

        $source = new SourceManifest();
        $build = $source->compare($manifest);
        $version = $build['build'];

        // Convert version to semantic versioning, if necessary
        if (intval($version) === $version) {
            $version = '1.0.' . $version;
        }

        // Query security advisories for all October packages
        $advisories = $this->getAdvisories($version);

        if (!count($advisories)) {
            $this->success('Your version of October CMS (' . $version . ') has no known security advisories.');
            return 0;
        }

        $this->error(
            'Your version of October CMS has ' .
            (
                (count($advisories) === 1)
                    ? '1 known security advisory'
                    : count($advisories) . ' known security advisories'
            )
            . ':'
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

        $this->line('');
        $this->error('You should upgrade to at least October CMS ' . $minReqVersion . ' as soon as possible.');

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

        foreach (['rain', 'backend', 'cms', 'system'] as $package) {
            $sourceAdvisories = $github->api('graphql')->execute('{
                securityVulnerabilities(ecosystem: COMPOSER, package: "october/' . $package . '", first: 100) {
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
