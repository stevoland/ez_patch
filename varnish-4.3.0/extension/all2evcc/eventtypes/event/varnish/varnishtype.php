<?php
include_once("extension/all2evcc/classes/all2evccclass.php");

class varnishType extends eZWorkflowEventType
{
    const WORKFLOW_TYPE_STRING = 'varnish';
    const WORKFLOW_TYPE_CLASS = 'varnishType';
    const WORKFLOW_TYPE_DESC = 'Varnish Hash Purging';
    
    function varnishType()
    {
        $this->eZWorkflowEventType( self::WORKFLOW_TYPE_STRING,  self::WORKFLOW_TYPE_DESC );
        $this->setTriggerTypes( array( 'content' =>  array(  'publish' => array( 'after', 'before' ), 
                                       						 'sort' => array( 'after', 'before' ), 
                                       						 'move' => array( 'after', 'before' ), 
                                       						 'swap' => array( 'after', 'before' ), 
                                       						 'updatemainassignment' => array( 'after', 'before' ), 
                                      						 'addlocation' => array( 'after', 'before' ), 
                                       						 'removelocation' => array( 'after', 'before' ), 
                                       						 'updatepriority' => array( 'after', 'before' ), 
                                       						 'hide' => array( 'after', 'before' ), 
                                       						 'delete' => array( 'after', 'before' ), 
                                       						 'updatesection' => array( 'after', 'before' ), 
                                       						 'updateinitiallanguage' => array( 'after', 'before' ), 
                                       						 'updatealwaysavailable' => array( 'after', 'before' ), 
                                       						 'removetranslation' => array( 'after', 'before' ), 
                                       						 'updateobjectstate' => array( 'after', 'before' ) )
                                       
        ) );
    }

    function execute( $process, $event )
    {
        $ini = eZINI::instance( 'all2evarnish.ini' );
        $filterType = $ini->variable( "Classes", 'ClassFilterType' );
        $filterArray = $ini->variable( "Classes", 'ClassFilterArray' );
        
        $parameters = $process->attribute( 'parameter_list' );
        $publishedObject = ezContentObject::fetch($parameters["object_id"]);
		    
		if( !is_object($publishedObject) )
		{
            $this->cleanUp();
		        //eZDebug::writeDebug( "Couldn't find the current Object", "VarnishWorkflow" );
            //return eZWorkflowType::STATUS_REJECTED;
        }
        else
        {
            $objectClass = $publishedObject->attribute("class_identifier");
            
            if( $filterType == "include" )
            {
                if( in_array( $objectClass, $filterArray ) || empty( $filterArray ) )
                {
                    $this->cleanUp();
                }
            }
            else
            {
                if( !in_array( $objectClass, $filterArray ) || empty( $filterArray ) )
                {
                    $this->cleanUp();
                }
            }
        }
        
        return eZWorkflowType::STATUS_ACCEPTED;
    }
    
    function cleanUp()
    {
        $ini = eZINI::instance( "all2evarnish.ini" );
        $blocknames = $ini->variable( "WorkflowSettings", "VarnishDomains" );
        $varnish = new all2eVarnish();
        $varnish->purgeDomain($blocknames);
    }
}

eZWorkflowEventType::registerEventType( varnishType::WORKFLOW_TYPE_STRING, varnishType::WORKFLOW_TYPE_CLASS );

?>
