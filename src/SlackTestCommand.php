<?php

namespace jmaloneytrevetts\bagistohubexport;

use Illuminate\Console\Command;
use jmaloneytrevetts\bagistohubexport\OrderHub;


class SlackTestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hub:slacktest';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Slack Integration';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        OrderHub::testSlack();
    }
}
