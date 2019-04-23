<?php

namespace EID\Console\Commands;

use Illuminate\Console\Command;
use EID\Mongo;
use EID\Models\LiveData;

class EidColumnUpdater extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'eidupdate:source';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Adds a new column, source. It sets cphl to be the default source of data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->mongo=Mongo::connect();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        ini_set('memory_limit', '2500M');
        //
        $this->comment("Engine has started at :: ".date('YmdHis'));
        
        
        $this->updateSourceField();
        
        $this->comment("Engine has stopped at :: ".date('YmdHis'));

    }

   
   /*
        db.collection.update(
           {source:{$ne:'poc'}},
           {source:'cphl'},
           {
             
             multi: true,
             
           }
        )
   */
    private function updateSourceField(){
        $this->comment("Source update started");

        $update_array = array(
            'source' => array('$ne' => 'poc'),
            'source' => 'cphl',
            array('multi'=> true)
            );

        $addNewFieldArray = array('$set' => array(
            
                'source' => 'cphl',
            ));
        $optionsArray = array('multiple' => true );

        $result=$this->mongo->eid_dashboard->update(
                array('source' => array('$ne' => 'poc')),
                $addNewFieldArray,
                $optionsArray
            );
        $this->comment($result);
       $this->comment("Source update ended successfully";
    }


}
