<?php

/*
    all2evarnishclass
*/

class all2eVarnish
{
    const STATUS_SUCCESS = 0;
    const STATUS_FAILED = 1;
    const SUCCESS_CODE = 200;

    public $hostname;
    public $port;
    public $timeout;
    public $domainnames;


    /*
        Constructor:
        Automaticly read out the settings from all2evarnish.ini
    */
    function __construct()
    {
        $this->readOutSettings();
    }


    /*
        function readOutSettings():
        Read out the settings from the all2evarnish.ini and set them in object
    */
    function readOutSettings()
    {
        $ini = eZINI::instance( "all2evarnish.ini" );
        list( $this->hostname, $this->port, $this->timeout ) = $ini->variableMulti( "ServerSettings", array( "DefaultHost", "DefaultPort", "DefaultTimeOut" ) );
    }

    /*
        function start():
        Starts the varnish Server
    */
    function start()
    {
        $request = "start\r\n";
        $erg = $this->execute( $request, $this->hostname, $this->port, $this->timeout );
        return $erg;
    }

    /*
        function stop():
        Stops the varnish Server
    */
    function stop()
    {
        $request = "stop\r\n";
        $erg = $this->execute( $request, $this->hostname, $this->port, $this->timeout );
        return $erg;
    }


    /*
        function status():
        Returns the status from varnish Server
    */
    function status()
    {
        $request = "status\r\n";
        $erg = $this->execute( $request, $this->hostname, $this->port, $this->timeout );
        return $erg;
    }


    /*
        function stats():
        Returns the stats from varnish Server
    */
    function stats()
    {
        $request = "stats\r\n";
        $erg = $this->execute( $request, $this->hostname, $this->port, $this->timeout );
        return $erg;
    }

    /*
        function freeRequest():
        Sends a free request to Varnish Telnet and returns the result
    */
    function freeRequest()
    {
        $http = eZHTTPTool::instance();
        if( $http->hasPostVariable( 'FreeTextRequest' ) && trim($http->postVariable( 'FreeTextRequest' )) != "" )
        {
            $request = $http->postVariable( 'FreeTextRequest' ) . "\r\n";
        }
        else
        {
            $request = "help\r\n";
        }
        $erg = $this->execute( $request, $this->hostname, $this->port, $this->timeout );
        $erg["terminal"] = substr( $request, 0, strpos( $request, "\r\n" ) );
        return $erg;
    }


    /*
        function purgeUrl():
        Purges the whole Server?
    */
    function purgeAll()
    {
        $request = "purge.url .\r\n";
        $erg = $this->execute( $request, $this->hostname, $this->port, $this->timeout );
        return $erg;
    }

    /*
        function purgeDomain():
        Clears the cache for the given Blocknames
    */
    function purgeDomain($blocknames=array("default"))
    {
        $erg = array();

        if( !is_array($blocknames) )
        {
            $blocknames = array($blocknames);
        }
        foreach( $blocknames as $blockname )
        {
            $ini = eZINI::instance( "all2evarnish.ini" );
            list( $this->hostname, $this->port, $this->timeout, $this->domainnames ) = $ini->variableMulti( $blockname, array( "Host", "Port", "TimeOut", "VarnishDomainNames" ) );

            foreach($this->domainnames as $dN)
            {
                $request = "purge req.http.X-Hash ~ \"^".$dN."\"\r\n";
                $erg[] = $this->execute( $request, $this->hostname, $this->port, $this->timeout );
            }
        }

        return $erg;
    }


    /*
        Regulates the Control of the Varnish Server
    */
    static function execute( $request, $host, $port, $timeout )
    {
        // if one of the parameters is not set, take them from ini-settings
        if( !$host || !$port || !$timeout )
        {
            eZDebug::writeDebug( "No settings were given, take default from the ini" );
            $ini = eZINI::instance( "all2evarnish.ini" );
            list( $host, $port, $timeout ) = $ini->variableMulti( "ServerSettings", array( "DefaultHost", "DefaultPort", "DefaultTimeOut" ) );
        }

        eZDebug::writeNotice( "Connecting to Server ".$host.":".$port." with request: ".$request );

        // Open socket connection to server
        $fp = fsockopen( $host,
                         $port,
                         $errorNumber,
                         $errorString,
                         $timeout );
        // if connection to socket fails
        if ( !$fp || $errorNumber != 0 )
        {
            eZDebug::writeError( "Connection failed. ".$errorString );
            return array( "Status"  => self::STATUS_FAILED,
                          "Message" => $errorString );
        }

        // send a message to server
        if( !fwrite( $fp, $request ) )
        {
            fclose( $fp );
            eZDebug::writeError( "Sending failed. Can't send message to server." );
            return array( "Status"  => self::STATUS_FAILED,
                          "Message" => "Sending failed. Can't send message to server." );
        }

        // read the message which came from server
        $readOut='';
        do{
      			$readOut.=fread($fp,4096);
      			$status=socket_get_status($fp);
    		}while($status['unread_bytes']);

        // Search for SUCCESS_CODE in the data
        $erg = preg_match( "/^".self::SUCCESS_CODE."/", $readOut );

        // returns an error if STATUS_CODE not founded
        if( !$erg )
        {
            fclose( $fp );
            eZDebug::writeError( $request." failed. ".$readOut );
            return array( "Status"  => self::STATUS_FAILED,
                          "Message" => $request." failed. ".$readOut );
        }

        eZDebug::writeNotice( $request." Succeed!" );

        // close the socket
        fclose( $fp );
        return array( "Status"  => self::STATUS_SUCCESS,
                      "Message" => "Send request: ".$request." Succeed: ".$readOut );
    }
}

?>
