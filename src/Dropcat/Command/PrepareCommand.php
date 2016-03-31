<?php

namespace Dropcat\Command;

use Dropcat\Services\Configuration;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;


class PrepareCommand extends Command
{
    /** @var Configuration configuration */
    private $configuration;

    public function __construct(Configuration $conf)
    {
        $this->configuration = $conf;
        parent::__construct();
    }

    protected function configure()
    {
        $HelpText = 'The <info>prepare</info> command setups what is needed for a drupal site on a remote server.
<comment>Samples:</comment>
To run with default options (using config from dropcat.yml in the currrent dir):
<info>dropcat prepare</info>
To override config in dropcat.yml, using options:
<info>dropcat prepare -url http://mysite --drush-alias=mysite</info>';

        $this->setName("prepare")
            ->setDescription("Prepare site")
            ->setDefinition(
                array(
                    new InputOption(
                        'drush_folder',
                        'df',
                        InputOption::VALUE_OPTIONAL,
                        'Drush folder',
                        $this->configuration->localEnvironmentDrushFolder()
                    ),
                    new InputOption(
                        'drush_alias',
                        'd',
                        InputOption::VALUE_OPTIONAL,
                        'Drush alias',
                        $this->configuration->siteEnvironmentDrushAlias()
                    ),
                    new InputOption(
                        'server',
                        's',
                        InputOption::VALUE_OPTIONAL,
                        'Server',
                        $this->configuration->remoteEnvironmentServerName()
                    ),
                    new InputOption(
                        'user',
                        'u',
                        InputOption::VALUE_OPTIONAL,
                        'User (ssh)',
                        $this->configuration->remoteEnvironmentSshUser()
                    ),

                    new InputOption(
                        'web_root',
                        'w',
                        InputOption::VALUE_OPTIONAL,
                        'Web root',
                        $this->configuration->remoteEnvironmentWebRoot()
                    ),
                    new InputOption(
                        'alias',
                        'a',
                        InputOption::VALUE_OPTIONAL,
                        'Symlink alias',
                        $this->configuration->remoteEnvironmentAlias()
                    ),
                    new InputOption(
                        'url',
                        'url',
                        InputOption::VALUE_OPTIONAL,
                        'Site url',
                        $this->configuration->siteEnvironmentUrl()
                    ),
                    new InputOption(
                        'site_name',
                        'sn',
                        InputOption::VALUE_OPTIONAL,
                        'Site url',
                        $this->configuration->siteEnvironmentName()
                    ),
                    new InputOption(
                        'mysql_host',
                        'mh',
                        InputOption::VALUE_OPTIONAL,
                        'Mysql host',
                        $this->configuration->mysqlEnvironmentHost()
                    ),
                    new InputOption(
                        'mysql_port',
                        'mp',
                        InputOption::VALUE_OPTIONAL,
                        'Mysql port',
                        $this->configuration->mysqlEnvironmentPort()
                    ),
                    new InputOption(
                        'mysql_db',
                        'md',
                        InputOption::VALUE_OPTIONAL,
                        'Mysql db',
                        $this->configuration->mysqlEnvironmentDataBase()
                    ),
                    new InputOption(
                        'mysql_user',
                        'mu',
                        InputOption::VALUE_OPTIONAL,
                        'Mysql user',
                        $this->configuration->mysqlEnvironmentUser()
                    ),
                    new InputOption(
                        'mysql_password',
                        'mpd',
                        InputOption::VALUE_OPTIONAL,
                        'Mysql password',
                        $this->configuration->mysqlEnvironmentPassword()
                    ),
                )
            )
            ->setHelp($HelpText);

    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $input = new ArgvInput();
        $env = $input->getParameterOption(array('--env', '-e'), getenv('SYMFONY_ENV') ?: 'dev');
        
        $drush_folder = $input->getOption('drush_folder');
        $drush_alias = $input->getOption('drush_alias');
        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $web_root = $input->getOption('web_root');
        $alias = $input->getOption('alias');
        $url = $input->getOption('url');
        $site_name = $input->getOption('site_name');
        $mysql_host = $input->getOption('mysql_host');
        $mysql_port = $input->getOption('mysql_host');
        $mysql_db = $input->getOption('mysql_db');
        $mysql_user = $input->getOption('mysql_user');
        $mysql_password = $input->getOption('mysql_password');

        $alias_content = '<?php

$aliases["' . $site_name . '"] = array (
        "remote-host" => "' . $server . '",
        "remote-user" => "' . $user . '",
        "root" => "' . $web_root . '/' . $alias . '",
        "uri"  => "' . $url . '",
    );
        ';

        $drush_file = new Filesystem();
        try {
            $drush_file->dumpFile($drush_folder .  '/'. $drush_alias . '.aliases.drushrc.php', $alias_content);
        } catch (IOExceptionInterface $e) {
            echo "An error occurred while creating your file at ".$e->getPath();
        }

        $process = new Process(
            "mysqladmin -u $mysql_user -p $mysql_password -h $mysql_host -P $mysql_port create $mysql_db"
        );
        $process->run();
        // executes after the command finishes
        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
        echo $process->getOutput();

        $output->writeln('<info>Task: prepare finished</info>');
    }
}
