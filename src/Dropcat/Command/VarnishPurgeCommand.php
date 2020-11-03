<?php

namespace Dropcat\Command;

use Dropcat\Lib\DropcatCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class VarnishPurgeCommand extends DropcatCommand
{
    protected function configure()
    {
        $HelpText = 'The <info>varnish:purge</info> command will purge all entries on varnish.
        <comment>Samples:</comment>
        To run with default options (using config from dropcat.yml in the current dir):
        <info>dropcat varnish:purge</info>
        To override config in dropcat.yml, using options:
        <info>dropcat varnish:purge --url=http://mysite.foo --varnish-port=80</info>';
        $this->setName("varnish:purge")
          ->setDescription("Purge your varnish instance")
        ->setDefinition(
            array(
            new InputOption(
                'varnish-ip',
                'vi',
                InputOption::VALUE_OPTIONAL,
                'Varnish IP (normal is external IP)',
                $this->configuration->deployVarnishIP()
            ),
            new InputOption(
                'varnish-port',
                'Varnish port',
                InputOption::VALUE_OPTIONAL,
                'To',
                $this->configuration->deployVarnishPort()
            ),
            new InputOption(
                'url',
                'u',
                InputOption::VALUE_OPTIONAL,
                'Site url',
                $this->configuration->siteEnvironmentUrl()
            ),
              )
        )
          ->setHelp($HelpText);
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {

        # we neeed to handle errors...
        # https://stackoverflow.com/questions/40096022/how-to-handle-fsockopen-error-in-php
        $varnish_port = $input->getOption('varnish-port');
        $varnish_ip = $input->getOption('varnish-ip');
        $url = $input->getOption('url');
        // Open the socket
        $errno = ( integer)"";
        $errstr = ( string)"";
        if ($varnish_port && $varnish_ip) {
            $varnish_sock = fsockopen(
                $this->configuration->deployVarnishIP(),
                $this->configuration->deployVarnishPort(),
                $errno,
                $errstr,
                10
            );

            if ($url) {
                $target = $url;
            } else {
                $target = $this->configuration->siteEnvironmentUrl();
            }
            $host = parse_url($target, PHP_URL_HOST);

            // Prepare the command to send
            $cmd = "DOMAINPURGE / HTTP/1.0\r\n";
            $cmd .= "Host: " . $host . "\r\n";
            $cmd .= "Connection: Close\r\n";
            $cmd .= "\r\n";

            // Send the request
            fwrite($varnish_sock, $cmd);

            $response = "";
            while (!feof($varnish_sock)) {
                $response .= fgets($varnish_sock, 128);
            }
            if ($output->isVerbose()) {
                print $response;
            }

            // Close the socket
            fclose($varnish_sock);
        } else {
            throw new \RuntimeException(
                'No configuration related with varnish deploy environment',
                111
            );
        }

        return 0;
    }
}

//
// Example Varnish configuration
// sub vcl_recv {
//   # Allow PURGE all domain
//   if (req.method == "DOMAINPURGE") {
//     if (!client.ip ~ purge) {
//       return(synth(405,"Not allowed."));
//     }
//     ban("obj.http.x-host == " + req.http.host);
//     return (synth(200, "Ban added."));
//  }
// sub vcl_backend_response {
//   set beresp.http.x-host = bereq.http.Host;
// }
// sub vcl_deliver {
//   # remove the header from response, only used for bans.
//   unset resp.http.x-host;
//}
