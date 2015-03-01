<?php

namespace League\CLImate\Argument;

class Parser {

    /**
     * Filter class to find various types of arguments
     *
     * @var League\CLImate\Argument\Filter $filter
     */
    protected $filter;

    /**
     * Summary builder class
     *
     * @var League\CLImate\Argument\Summary $summary
     */
    protected $summary;

    public function __construct()
    {
        $this->summary = new Summary();
    }

    /**
     * @param Filter $filter
     * @param Argument[] $arguments
     *
     * @return \League\CLImate\Argument\Parser
     */
    public function setFilter($filter, $arguments)
    {
        $this->filter = $filter;
        $this->filter->setArguments($arguments);

        return $this;
    }

    /**
     * Parse command line arguments into CLImate arguments.
     *
     * @throws \Exception if required arguments aren't defined.
     * @param array $argv
     */
    public function parse(array $argv = null)
    {
        $cliArguments      = $this->arguments($argv);
        $unParsedArguments = $this->prefixedArguments($cliArguments);

        $this->nonPrefixedArguments($unParsedArguments);

        // After parsing find out which arguments were required but not
        // defined on the command line.
        $missingArguments = $this->filter->missing();

        if (count($missingArguments) > 0) {
            throw new \Exception(
                'The following arguments are required: '
                . $this->summary->short($missingArguments) . '.'
            );
        }
    }

    /**
     * Get the command name.
     *
     * @param array $argv
     *
     * @return string
     */
    public function command(array $argv = null)
    {
        return $this->getCommandAndArguments($argv)['command'];
    }

    /**
     * Get the passed arguments.
     *
     * @param array $argv
     *
     * @return string
     */
    public function arguments(array $argv = null)
    {
        return $this->getCommandAndArguments($argv)['arguments'];
    }

    /**
     * Parse command line options into prefixed CLImate arguments.
     *
     * Prefixed arguments are arguments with a prefix (-) or a long prefix (--)
     * on the command line.
     *
     * Return the arguments passed on the command line that didn't match up with
     * prefixed arguments so they can be assigned to non-prefixed arguments.
     *
     * @param array $argv
     * @return array
     */
    protected function prefixedArguments(array $argv = [])
    {
        foreach ($argv as $key => $cliArgument) {
            list($name, $value) = $this->getNameAndValue($cliArgument);

            $argv = $this->setPrefixedArgumentValue($argv, $key, $name, $value);
        }

        // Send un-parsed arguments back upstream.
        return array_values($argv);
    }

    /**
     * Parse unset command line options into non-prefixed CLImate arguments.
     *
     * Non-prefixed arguments are parsed after the prefixed arguments on the
     * command line, in the order that they're defined in the script.
     *
     * @param array $unParsedArguments
     */
    protected function nonPrefixedArguments(array $unParsedArguments = [])
    {
        foreach ($this->filter->withoutPrefix() as $key => $argument) {
            if (isset($unParsedArguments[$key])) {
                $argument->setValue($unParsedArguments[$key]);
            }
        }
    }

    /**
     * Parse the name and value of the argument passed in
     *
     * @param string $cliArgument
     * @return string[] [$name, $value]
     */
    protected function getNameAndValue($cliArgument)
    {
        // Look for arguments defined in the "key=value" format.
        if (strpos($cliArgument, '=') !== false) {
            return explode('=', $cliArgument, 2);
        }

        // If the argument isn't in "key=value" format then assume it's in
        // "key value" format and define the value after we've found the
        // matching CLImate argument.
        return [$cliArgument, null];
    }

    /**
     * Set the an argument's value and remove applicable
     * arguments from array
     *
     * @param array $argv
     * @param int $key
     * @param string $name
     * @param string|null $value
     *
     * @return array The new $argv
     */
    protected function setPrefixedArgumentValue($argv, $key, $name, $value)
    {
        // Look for the argument in our defined $arguments and assign their
        // value.
        if (!($argument = $this->findPrefixedArgument($name))) {
            return $argv;
        }

        // We found an argument key, so take it out of the array.
        unset($argv[$key]);

        // Arguments are given the value true if they only need to
        // be defined on the command line to be set.
        if ($argument->noValue()) {
            $argument->setValue(true);
            return $argv;
        }

        if (is_null($value)) {
            // If the value wasn't previously defined in "key=value"
            // format then define it from the next command argument.
            $argument->setValue($argv[++$key]);
            unset($argv[$key]);
            return $argv;
        }

        $argument->setValue($value);

        return $argv;
    }

    /**
     * Search for argument in defined prefix arguments
     *
     * @param string $name
     *
     * @return Argument|false
     */
    protected function findPrefixedArgument($name)
    {
        foreach ($this->filter->withPrefix() as $argument) {
            if (in_array($name, ["-{$argument->prefix()}", "--{$argument->longPrefix()}"])) {
                return $argument;
            }
        }

        return false;
    }

    /**
     * Pull a command name and arguments from $argv.
     *
     * @param array $argv
     * @return array
     */
    protected function getCommandAndArguments(array $argv = null)
    {
        // If no $argv is provided then use the global PHP defined $argv.
        if (is_null($argv)) {
            global $argv;
        }

        $arguments = $argv;
        $command   = array_shift($arguments);

        return compact('arguments', 'command');
    }

}
