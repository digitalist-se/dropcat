<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Dropcat\Lib\UUID;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Yaml\Yaml;
use Symfony\Component\Yaml\Exception\ParseException;
use phpseclib\Crypt\RSA;
use phpseclib\Net\SSH2;
use Exception;

/**
 *
 */
class RollbackCommand extends DropcatCommand
{

    /**
     *
     */
    protected function configure()
    {
        $HelpText = '<info>rollback</info> rollbacks site with given info.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat rollback</info>
To override config in dropcat.yml, using options:
<info>dropcat rollback --tracker-file=/my/dir/file.yml</info>';
        $this->setName("site:rollback")
            ->setAliases(["rollback"])
          ->setDescription("Rollback a site")
        ->setDefinition(
            [
            new InputOption(
                'tracker-file',
                't',
                InputOption::VALUE_OPTIONAL,
                'Trackerfile',
                $this->configuration->trackerFile()
            ),
            new InputOption(
                'id',
                'i',
                InputOption::VALUE_OPTIONAL,
                'Id (used for backups done during rollback, og not set a UUID will be generated instead',
                $this->configuration->rollbackId()
            ),
              ]
        )
          ->setHelp($HelpText);
    }

    /**
     *
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $tracker_file = $input->getOption('tracker-file');
        $rollback_id = $input->getOption('id');

        $rollback = [];

        if (!isset($rollback_id)) {
            $uuid = new UUID();
            $rollback_id = $uuid->v4();
        }

        try {
            $rollback = Yaml::parse(file_get_contents($tracker_file));
        } catch (ParseException $e) {
            echo $e->getMessage() . "\n";
        }
        $sites = $rollback['sites'];

        foreach ($sites as $site => $siteProperty) {
            if (!isset($siteProperty['db']['host'])) {
                throw new Exception('db host missing');
            }
            $this->movedir(
                $siteProperty['web']['host'],
                $siteProperty['web']['user'],
                $siteProperty['web']['port'],
                $siteProperty['web']['id-file'],
                $siteProperty['web']['pass'],
                $siteProperty['web']['site-path'],
                $siteProperty['web']['alias-path']
            );
            $output->writeln('<info>' . $this->mark .
              ' site rollback finished</info>');
            // Do db backup.
            $this->dumpDb(
                $siteProperty['db']['host'],
                $siteProperty['db']['user'],
                $siteProperty['db']['pass'],
                $siteProperty['db']['name'],
                $rollback_id
            );
            $this->dropDb(
                $siteProperty['db']['host'],
                $siteProperty['db']['user'],
                $siteProperty['db']['pass'],
                $siteProperty['db']['name']
            );
            $this->createDb(
                $siteProperty['db']['host'],
                $siteProperty['db']['user'],
                $siteProperty['db']['pass'],
                $siteProperty['db']['name']
            );
            $this->insertDb(
                $siteProperty['db']['host'],
                $siteProperty['db']['user'],
                $siteProperty['db']['pass'],
                $siteProperty['db']['name'],
                $siteProperty['db']['dump']
            );
        }

        $output->writeln('<info>' . $this->mark .
          ' db rollback finished</info>');
    }
    protected function movedir($server, $user, $port, $key, $pass, $path, $alias)
    {
        $ssh = new SSH2($server, $port);
        $ssh->setTimeout(999);
        $auth = new RSA();
        if (isset($pass)) {
            $auth->setPassword($pass);
        }
        $identity_file_content = file_get_contents($key);
        $auth->loadKey($identity_file_content);

        try {
            $login = $ssh->login($user, $auth);
            if (!$login) {
                throw new Exception('Login Failed using ' . $key . ' at port '
                  . $port . ' and user ' . $user . ' at ' . $server
                  . ' ' . $ssh->getLastError());
            }
        } catch (Exception $e) {
            echo $e->getMessage() . "\n";
            exit(1);
        }

        $ssh->exec("rm $alias && ln -snf $path $alias");

        $status = $ssh->getExitStatus();
        if ($status !== 0) {
            echo "Could not set path, error code $status\n";
            $ssh->disconnect();
            exit($status);
        }
        $ssh->disconnect();
        return $path;

        // login to apache, remove old symlink, add new symlink to dir in tracker.
    }
    protected function dumpDb($dbhost, $dbuser, $dbpass, $dbname, $id)
    {
        $mysql = "mysqldump -h $dbhost -u $dbuser -p$dbpass $dbname > /tmp/$dbname" . '_' . "$id" . '.sql';
        $dump = new Process($mysql);
        $dump->run();
        if (!$dump->isSuccessful()) {
            throw new ProcessFailedException($dump);
        }
    }
    protected function dropDb($dbhost, $dbuser, $dbpass, $dbname)
    {
        $mysql = "mysqladmin -h $dbhost -u $dbuser -p$dbpass drop $dbname -f";
        $drop = new Process($mysql);
        $drop->run();
        if (!$drop->isSuccessful()) {
            throw new ProcessFailedException($drop);
        }
    }
    protected function createDb($dbhost, $dbuser, $dbpass, $dbname)
    {
        $mysql = "mysqladmin -h $dbhost -u $dbuser -p$dbpass create $dbname";
        $create = new Process($mysql);
        $create->run();
        if (!$create->isSuccessful()) {
            throw new ProcessFailedException($create);
        }
    }
    protected function insertDb($dbhost, $dbuser, $dbpass, $dbname, $dump)
    {
        $mysql = "mysql -h $dbhost -u $dbuser -p$dbpass $dbname < $dump";
        $insert = new Process($mysql);
        $insert->run();
        if (!$insert->isSuccessful()) {
            throw new ProcessFailedException($insert);
        }
    }
}
