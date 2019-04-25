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
    protected $signature = 'eidupdate:all';

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
        $this->updatePCR();
        
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
    
       $this->comment("Source update ended successfully");
    }


    private function updatePCR(){
        $this->comment("PCR updates started");

        $year=2014;
        $current_year=date('Y');
       
       
        while($year<=$current_year){
            $samples_records = LiveData::getPCRs($year);
            $counter=0;
            
            try {
                foreach($samples_records AS $s){
                    $this->augmentSampleRecord(
                    $s->id,
                    'pcr',$s->pcr_name
                    );
                   $counter ++;
                }//end of for loop
              echo " Updated $counter PCR records for $year\n";
              $year++;
            } catch (Exception $e) {
                var_dump($e);
            }//end catch

        }//end of while loop

        $this->comment("PCR updates ended successfully");

    }

    private function augmentSampleRecord($sampleId,$field,$value){
        
        $addNewFieldArray = array('$set' => array(
            $field=>$value
            ));
        $result=$this->mongo->eid_dashboard->update(array('sample_id' => $sampleId), $addNewFieldArray);
       // var_dump($result);
        //return $result['n'];//return 1 for when a record has been successfully removed,0 when nothing has been found.
    }


}
