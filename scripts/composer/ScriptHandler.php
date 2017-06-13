<?php

namespace DrupalProject\composer;

use Composer\Script\Event;
use Composer\Semver\Comparator;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

/**
 * Script Handler Class.
 */
class ScriptHandler {

  /**
   * Get drupal root.
   */
  protected static function getDrupalRoot($project_root) {
    return $project_root . '/docroot';
  }

  /**
   * Create required files.
   */
  public static function createRequiredFiles(Event $event) {
    $fs = new Filesystem();
    $root = static::getDrupalRoot(getcwd());

    $dirs = [
      'modules',
      'profiles',
      'themes',
    ];

    foreach ($dirs as $dir) {
      if (!$fs->exists($root . '/' . $dir)) {
        $fs->mkdir($root . '/' . $dir);
        $fs->touch($root . '/' . $dir . '/.gitkeep');
      }
    }

    if (!$fs->exists($root . '/sites/default/settings.php') and $fs->exists($root . '/sites/default/default.settings.php')) {
      $fs->copy($root . '/sites/default/default.settings.php', $root . '/sites/default/settings.php');
      $fs->chmod($root . '/sites/default/settings.php', 0666);
      $event->getIO()->write("Create a sites/default/settings.php file with chmod 0666");
    }

    if (!$fs->exists($root . '/sites/default/services.yml') and $fs->exists($root . '/sites/default/default.services.yml')) {
      $fs->copy($root . '/sites/default/default.services.yml', $root . '/sites/default/services.yml');
      $fs->chmod($root . '/sites/default/services.yml', 0666);
      $event->getIO()->write("Create a sites/default/services.yml file with chmod 0666");
    }

    if (!$fs->exists($root . '/sites/default/files')) {
      $oldmask = umask(0);
      $fs->mkdir($root . '/sites/default/files', 0777);
      umask($oldmask);
      $event->getIO()->write("Create a sites/default/files directory with chmod 0777");
    }
  }

  /**
   * Create required files.
   */
  public static function setupVm(Event $event) {

    $fs = new Filesystem();

    $settings = Yaml::parse(file_get_contents(getcwd() . '/config/default.config.yml'));

    $defaultName = explode('/', getcwd());
    $defaultName = array_pop($defaultName);

    $settings['vagrant_machine_name'] = $event->getIo()->ask('Enter your dev domain (<name>.dev) [' . $defaultName . ']:', $defaultName);
    $settings['vagrant_hostname'] = $settings['vagrant_machine_name'] . '.dev';


    $extras = [
      'solr' => FALSE,
      'nodejs' => TRUE,
      'drush' => TRUE,
      'blackfire' => TRUE,
      'redis' => TRUE,
      'pimpmylog' => TRUE,
      'adminer' => TRUE,
      'xdebug' => FALSE,
      'xhprof' => FALSE,
      'mailhog' => TRUE,
    ];

    $extras['solr'] = $event->getIo()->askConfirmation('Install solr [N,y]:', NULL);
    $extras['nodejs'] = $event->getIo()->askConfirmation('Install nodejs [Y,n]:', TRUE);
    $extras['blackfire'] = $event->getIo()->askConfirmation('Install blackfire [N,y]:', NULL);
    $extras['redis'] = $event->getIo()->askConfirmation('Install redis [N,y]:', NULL);
    $extras['xhprof'] = $event->getIo()->askConfirmation('Install xhprof [N,y]:', NULL);
    $extras['xdebug'] = $event->getIo()->askConfirmation('Install xdebug [N,y]:', NULL);
    $extras['mailhog'] = $event->getIo()->askConfirmation('Install xdebug [Y,n]:', NULL);

    $extras = array_filter($extras);
    $extras = array_keys($extras);
    $settings['installed_extras'] = $extras;

    $composer = $event->getComposer();
    $event->getIO()->write("Write config for drupal-vm");

    $yaml = Yaml::dump($settings);
    file_put_contents(getcwd() . '/config/config.yml', $yaml);
  }

  /**
   * How help.
   */
  public static function displayHelp(Event $event) {
    $event->getIO()->write([
      '*** How to use this project ***',
      '** Setup vm configuration **',
      'composer vm:setup',
      '** Start vm **',
      'composer vm:start',
      '** Provisioning vm **',
      'composer vm:provision',
    ]);
  }

  /**
   * Checks if the installed version of Composer is compatible.
   *
   * Composer 1.0.0 and higher consider a `composer install` without having a
   * lock file present as equal to `composer update`. We do not ship with a lock
   * file to avoid merge conflicts downstream, meaning that if a project is
   * installed with an older version of Composer the scaffolding of Drupal will
   * not be triggered. We check this here instead of in drupal-scaffold to be
   * able to give immediate feedback to the end user, rather than failing the
   * installation after going through the lengthy process of compiling and
   * downloading the Composer dependencies.
   *
   * @see https://github.com/composer/composer/pull/5035
   */
  public static function checkComposerVersion(Event $event) {
    $composer = $event->getComposer();
    $io = $event->getIO();
    $version = $composer::VERSION;

    // The dev-channel of composer uses the git revision as version number,
    // try to the branch alias instead.
    if (preg_match('/^[0-9a-f]{40}$/i', $version)) {
      $version = $composer::BRANCH_ALIAS_VERSION;
    }

    // If Composer is installed through git we have no easy way to determine if
    // it is new enough, just display a warning.
    if ($version === '@package_version@' || $version === '@package_branch_alias_version@') {
      $io->writeError('<warning>You are running a development version of Composer. If you experience problems, please update Composer to the latest stable version.</warning>');
    }
    elseif (Comparator::lessThan($version, '1.0.0')) {
      $io->writeError('<error>Drupal-project requires Composer version 1.0.0 or higher. Please update your Composer before continuing</error>.');
      exit(1);
    }
  }

}
