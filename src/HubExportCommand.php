<?php

namespace jmaloneytrevetts\bagistohubexport;

use Illuminate\Console\Command;
use jmaloneytrevetts\bagistohubexport\OrderHub;


class HubExportCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'hub:export {orderID?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Exports Bagisto orders to Hub';

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
        $orderID = $this->argument('orderID');
        
        if ( $orderID ) {
            OrderHub::forceExport($orderID);
        } else {
            OrderHub::getOrdersThatNeedExporting();
        }
        
    }
}
