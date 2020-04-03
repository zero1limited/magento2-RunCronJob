<?php
namespace Zero1\RunCronJob\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;

class RunCronJob extends Command
{
    const CLI_INPUT_JOB_CODE = 'job-code';

    const EXIT_CODE_NO_JOB_CODE_SUPPLIED = 2;
    const EXIT_CODE_UNABLE_TO_FIND_JOB = 3;
    const EXIT_CODE_INVALID_JOB_CONFIG = 4;
    const EXIT_CODE_METHOD_UNCALLABLE = 5;

    /** @var \Magento\Framework\ObjectManagerInterface */
    protected $objectManager;

    /** @var \Magento\Cron\Model\ConfigInterface */
    protected $config;

    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\Cron\Model\ConfigInterface $config
    ){
        $this->objectManager = $objectManager;
        $this->config = $config;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName("cron:run-job");
        $this->setDescription("Run a specific job by job code.");
        $this->setDefinition([
            new InputOption(
                self::CLI_INPUT_JOB_CODE,
                null,
                InputOption::VALUE_REQUIRED,
                'The code of the job to run'
            ),
        ]);
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $jobCode = $input->getOption(self::CLI_INPUT_JOB_CODE);
        if(!$jobCode){
            $output->writeln('<error>You must provide a job code to run</error>');
            return self::EXIT_CODE_NO_JOB_CODE_SUPPLIED;
        }

        $output->writeln(sprintf('<info>Running job with code: %s</info>',  $jobCode));

        $jobGroups = $this->config->getJobs();

        $jobConfig = null;
        foreach($jobGroups as $groupId => $jobs){
            if(isset($jobs[$jobCode])){
                $jobConfig = $jobs[$jobCode];
                break;
            }
        }

        if(!$jobConfig){
            $output->writeln(sprintf('<error>Unable to match %s to a job.</error>', $jobCode));
            return self::EXIT_CODE_UNABLE_TO_FIND_JOB;
        }

        $output->writeln(sprintf(
            '<comment>Found %s, in group %s, with config %s</comment>',
            $jobCode,
            $groupId,
            json_encode($jobConfig)
        ));

        if (!isset($jobConfig['instance'], $jobConfig['method'])) {
            $output->writeln('<error>Invalid job config, missing "instance" and/or "method" </error>');
            return self::EXIT_CODE_INVALID_JOB_CONFIG;
        }

        $model = $this->objectManager->create($jobConfig['instance']);
        $method = $jobConfig['method'];
        $callback = [$model, $method];

        if(!is_callable($callback)){
            $output->writeln(sprintf(
                '<error>Unable to call %s->%s()</error>',
                get_class($model),
                $method
            ));
            return self::EXIT_CODE_METHOD_UNCALLABLE;
        }

        $output->writeln(sprintf(
            '<comment>Running %s->%s()</comment>',
            get_class($model),
            $method
        ));

        call_user_func($callback);

        $output->writeln('<info>Finished</info>');
    }
}