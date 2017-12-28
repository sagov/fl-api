<?php


namespace FL\Api\Tool\Command;

class Help
{
    /**
     * @var string
     */
    private $message = <<< EOH
Fl-API development mode.

Usage:

[-h|--help] --add

--help|-h                    Print this usage message.
--add NameModule             Create New Module Sceleton.
--log consumer start NameTopic        Start|Stop Consumer  

EOH;

    /**
     * Emit the help message.
     *
     * @param null|resource $stream Defaults to STDOUT
     */
    public function __invoke($stream = null)
    {
        if (! is_resource($stream)) {
            echo $this->message;
            return;
        }

        fwrite($stream, $this->message);
    }
}
