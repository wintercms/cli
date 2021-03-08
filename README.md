# Winter CMS CLI Helper

A command line tool to help users manage Winter CMS installations.

```
Usage:
  command [options] [arguments]

Options:
  -h, --help            Display this help message
  -q, --quiet           Do not output any message
  -V, --version         Display this application version
      --ansi            Force ANSI output
      --no-ansi         Disable ANSI output
  -n, --no-interaction  Do not ask any interactive question
  -v|vv|vvv, --verbose  Increase the verbosity of messages: 1 for normal output, 2 for more verbose output and 3 for debug

Available commands:
  help             Displays help for a command
  list             Lists commands
  self-update      [selfupdate] Self-updates the Winter CLI helper.
 github
  github:token     Sets the GitHub Access token for the helper.
 install
  install:check    Checks the current environment that it can run Winter CMS.
 project
  project:alerts   View security alerts for an Winter CMS project.
  project:version  Determines the version of Winter CMS in use in a project.
```

## Installation

You can download the latest PHAR release of this tool from the [Releases](https://github.com/wintercms/cli/releases) page. Download this file to your preferred location, then make the PHAR file executable (optionally, renaming it to `winter` if you wish):

```
mv winter.phar winter
chmod a+x winter
```

Move this file into one of your `$PATH` directories to make this CLI helper available globally.

```
mv winter /usr/bin/winter
```

## Other Requirements

Some commands (such as the `project:alerts` command) use the GitHub API, and may not be accessible due to rate limits imposed by GitHub. To work around this, you can create an Access Token in your GitHub account and store this with the CLI helper for future use.

You can create the necessary token by going to the following URL:
https://github.com/settings/tokens/new?scopes=public_repo&description=Winter%20CMS%20CLI%20Helper

Once done, you can then use the `winter github:token` command, adding the token as an argument, to store the token and allow the CLI helper to use your token for future API calls.

## Usage

Run the tool by running `winter` (or `winter.phar`). By default, it will show the help screen with command line options and available commands. You can also add the `--help` option after any command to get help on specific commands.

## Commands

### `self-update`

```
winter self-update
winter selfupdate
```

This will update the Winter CLI helper to the latest version, if not already installed.

### `github:token`

```
winter github:token [token]
```

Registers a GitHub Access Token which is used for some commands that use the GitHub API and may be subject to rate limits from GitHub (such as the `project:alerts` command). This will be stored in the user's home directory for future use.

### `install:check`

```
winter install:check
```

This command allows you to check that your current environment can run Winter CMS.

The command checks that your PHP version is compatible, ensures that the necessary extensions are installed and that configuration settings for PHP are correctly set. If any requirements are not met, the command will give you suggestions on the necessary steps to take in order to make your environment compatible for Winter CMS.

### `project:alerts`

```
winter project:alerts [path]
```

This will compare the installed version of Winter CMS against the [database of security advisories](https://github.com/wintercms/winter/security/advisories) published by the Winter CMS maintainers, and will indicate whether your Winter CMS instance needs to be updated or not.

### `project:version`

```
winter project:version [-d|--detailed] [path]
```

This will detect the installed version of Winter CMS in the given path. The path is optional - if not provided, it will look in the current work directory.

The optional `-d|--detailed` option will also print out a list of modified, created or deleted files in the Winter CMS installation, if any changes have been made to the core Winter CMS files.
