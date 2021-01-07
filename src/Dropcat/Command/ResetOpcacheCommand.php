<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Dropcat\Lib\UUID;
use Dropcat\Lib\Write;
use Dropcat\Lib\Upload;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Dropcat\Lib\Remove;

class ResetOpcacheCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = '<info>Display info about installed drupal multi-sites</info>';

        $this->setName("reset:opcache")
            ->setDescription("Reset opcache on web host.")
            ->setHelp($HelpText)
          ->setDefinition(
              [
              new InputOption(
                  'drush-folder',
                  null,
                  InputOption::VALUE_OPTIONAL,
                  'Drush folder',
                  $this->configuration->localEnvironmentDrushFolder()
              ),
              new InputOption(
                  'drush-script',
                  null,
                  InputOption::VALUE_OPTIONAL,
                  'Drush script path (can be remote)'
              ),
              new InputOption(
                  'drush-alias',
                  'd',
                  InputOption::VALUE_OPTIONAL,
                  'Drush alias',
                  $this->configuration->getFullDrushAlias()
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
                  'ssh-port',
                  'p',
                  InputOption::VALUE_OPTIONAL,
                  'SSH port',
                  $this->configuration->remoteEnvironmentSshPort()
              ),
              new InputOption(
                  'ssh-key-password',
                  null,
                  InputOption::VALUE_OPTIONAL,
                  'SSH key password',
                  $this->configuration->localEnvironmentSshKeyPassword()
              ),
              new InputOption(
                  'ssh-key',
                  'i',
                  InputOption::VALUE_OPTIONAL,
                  'SSH key',
                  $this->configuration->remoteEnvironmentIdentifyFile()
              ),
              new InputOption(
                  'web-root',
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
                  null,
                  InputOption::VALUE_OPTIONAL,
                  'Site url',
                  $this->configuration->siteEnvironmentUrl()
              ),
              new InputOption(
                  'auth-user',
                  null,
                  InputOption::VALUE_OPTIONAL,
                  'Basic auth user',
                  null
              ),
                new InputOption(
                    'auth-pass',
                    null,
                    InputOption::VALUE_OPTIONAL,
                    'Basic auth pass',
                    null
                )
              ]
          );
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        $server = $input->getOption('server');
        $user = $input->getOption('user');
        $ssh_port = $input->getOption('ssh-port');
        $identity_file = $input->getOption('ssh-key');
        $ssh_key_password = $input->getOption('ssh-key-password');
        $web_root = $input->getOption('web-root');
        $alias = $input->getOption('alias');
        $url = $input->getOption('url');
        $auth_user = $input->getOption('auth-user');
        $auth_pass = $input->getOption('auth-pass');
        $timeout = '999';
        $verbose = false;

        if ($output->isVerbose()) {
            $verbose = true;
        }

        $remote_config = [
          'server' => $server,
          'user' => $user,
          'port' => $ssh_port,
          'key' => $identity_file,
          'pass' => $ssh_key_password,
          'timeout' => $timeout,
        ];

        // create a random named file to upload on server.
        $random_file_name = UUID::v4() . '.php';
        $write = new Write();
        $out = '<?php' . "\n";
        $out .= 'if (function_exists(\'opcache_reset\')) {' . "\n";
        $out .= '  $reset = opcache_reset();' . "\n";
        $out .= '  if ($reset === TRUE) { echo "Opcache cleared."; }' . "\n";
        $out .= '}' . "\n";
        $out .= 'if (function_exists(\'apc_clear_cache\')) {' . "\n";
        $out .= '  apc_clear_cache();' . "\n";
        $out .= '}' . "\n";
        $out .= "\n";

        $conf = [
          'name' => $random_file_name,
          'content' => $out,
        ];
        $write->file($conf);

        // upload the file.
        $from = "/tmp/$random_file_name";
        $to = "$web_root/$alias/web/$random_file_name";
        $upload_file = new Upload();
        $upload_file->place($remote_config, $from, $to, $verbose);

        // First call is to empty the opcache, second call is to warm up the opcache.
        $requestUrls = ["$url/$random_file_name", $url];
        foreach ($requestUrls as $delta => $requestUrl) {
            if (isset($auth_pass) && isset($auth_user)) {
                $requestUrls[$delta] = str_replace('://', "://$auth_user:$auth_pass@", $requestUrl);
            }
        }

        if ($verbose == true) {
            $output->writeln("'<comment>' . Curling: $requestUrls[0]" . '</comment>');
        }

        // We need all the curl output here to check for the response.
        $request = new Process(['curl', '-ik', "$requestUrls[0]"]);
        $request->setTimeout(10);
        $request->run();
        // Executes after the command finishes.
        $clearOutput = $request->getOutput();
        if ($verbose == true) {
            $output->writeln('<comment>' . $clearOutput . '</comment>');
        }
        if (!$request->isSuccessful() || strpos($clearOutput, "Opcache cleared.") === FALSE) {
            $output->writeln("<error>Opcache could not be cleared</error>");
            $output->writeln($request->getErrorOutput());
            throw new \Exception();
        }

        if ($verbose == true) {
            $output->writeln("'<comment>' . Curling: $requestUrls[1]" . '</comment>');
        }

        // Check only the headers here.
        $request = new Process(['curl', '-Ik', "$requestUrls[1]"]);
        $request->setTimeout(10);
        $request->run();
        // Executes after the command finishes.
        if ($verbose == true) {
            $output->writeln('<comment>' . $request->getOutput() . '</comment>');
        }
        if (!$request->isSuccessful()) {
            $output->writeln($request->getErrorOutput());
            throw new ProcessFailedException($request);
        }

        // remove the random named file from the server.
        $remove = new Remove();
        $remove->file($remote_config, $to, $verbose);

        $output->writeln('<info>' . $this->heart . ' opcache reset</info>');

        return 0;
    }
}
