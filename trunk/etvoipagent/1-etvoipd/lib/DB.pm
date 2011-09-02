#!/usr/bin/perl


package DB;

use strict;

use DBI;
use Data::Dumper;
use ETVA::Utils;

my ($dbh,$db_engine,$db_user,$db_pass,$db_host,$db_name,$db_file);

sub db_init {
    my (%p) = @_;    

    if (exists($p{"db_engine"})) {$db_engine = $p{"db_engine"};}
    if (exists($p{"db_user"})) {$db_user = $p{"db_user"};}
    if (exists($p{"db_pass"})) {$db_pass = $p{"db_pass"};}
    if (exists($p{"db_host"})) {$db_host = $p{"db_host"};}
    if (exists($p{"db_name"})) {$db_name = $p{"db_name"};}
    if (exists($p{"db_file"})) {$db_file = $p{"db_file"};}    

    plog("DB settings initialized");    
    
}

sub db_quote {
    my ($str) = @_;
    my $quoted;

    if($str eq ""){ $str = "";}
    
    if($dbh) { $quoted = $dbh->quote($str);}
    else{ $quoted = $str;}
    
    return $quoted;
}

sub db_connect {    
    my (%p) = @_;    

    if(%p) {db_init(%p);}    
        
    if ( $db_engine eq "mysql" ) {        
        $dbh = DBI->connect_cached("dbi:mysql:dbname=$db_name;host=$db_host", "$db_user", "$db_pass");
    }
    elsif ( $db_engine eq "pgsql" ) {
        $dbh = DBI->connect_cached("dbi:pgsql:dbname=$db_name;host=$db_host", "$db_user", "$db_pass");
    }
    elsif ( $db_engine eq "sqlite" ) {                
        $dbh = DBI->connect_cached("dbi:SQLite2:dbname=$db_file","","");
    }
    elsif ( $db_engine eq "sqlite3" ) {                
        $dbh = DBI->connect_cached("dbi:SQLite:dbname=$db_file","","");
    }

    unless( $dbh ){
        plog("DB connection failed");
        return 0;
    }

    plog("DB connection ok");        
    return $dbh;

}


sub db_sql_multi {
    my ($sql, @fields, $sth, $rc);
    $sql = shift;
    @fields = @_;

    if(! $sql ){
        plog("Need to pass SQL statement");
        return 0;
    }

    $dbh = db_connect();

    # verify connection to database
    if(!$dbh){

       # reconnect
       if (! db_connect() )
       {
           plog("Could not connect to DB");
           return 0;
       }

    }

    $sth = $dbh->prepare($sql);

    if(!$sth){

        plog("Failed to prepare SQL statement");

        plog("$DBI::errstr");

        #
        # Check for a connection error -- should not occur
        #
        if ($DBI::errstr =~ /Connection failure/i)
        {
            if (! db_connect() )
            {
                plog("Unable to connect to database");
                return 0;
            }
            else
            {
                plog("Database connection re-established, attempting to prepare again");

                $sth = $dbh->prepare($sql);
            }
        }

        #
        # Check to see if we recovered
        #
        if ( ! defined( $sth ) || ! ($sth) )
        {
            plog("Unable to prepare SQL statement");
            plog("$DBI::errstr");

            return 0;
        }
    }

    #
    # Attempt to execute our prepared statement
    #    
    
    foreach my $item (@fields) {

        $rc = $sth->execute(@$item);
        if (! defined( $rc ) )
        {
            #
            # We failed, print the error message for troubleshooting
            #

            plog("Unable to execute prepared SQL statement");
            plog("$DBI::errstr");

            return 0;
        }
           
    }
    
    #
    # All is successful, return the statement handle
    #
    
    return wantarray() ? ($sth,$rc) : {"_handler"=>$sth,"_result"=>$rc}

    


}

sub db_sql {
    my ($sql, $sth, $rc);

    $sql = shift;    
    
    if(! $sql ){
        plog("Need to pass SQL statement");
        return 0;
    }

    $dbh = db_connect();

    # verify connection to database
    if(!$dbh){

       # reconnect
       if (! db_connect() )
       {
           plog("Could not connect to DB");
           return 0;           
       }

    }
    
    $sth = $dbh->prepare($sql);
        
    if(!$sth){

        plog("Failed to prepare SQL statement");

        plog("$DBI::errstr");

        #
        # Check for a connection error -- should not occur
        #
        if ($DBI::errstr =~ /Connection failure/i)
        {
            if (! db_connect() )
            {
                plog("Unable to connect to database");
                return 0;
            }
            else
            {
                plog("Database connection re-established, attempting to prepare again");

                $sth = $dbh->prepare($sql);
            }
        }

        #
        # Check to see if we recovered
        #
        if ( ! defined( $sth ) || ! ($sth) )
        {            
            plog("Unable to prepare SQL statement");
            plog("$DBI::errstr");

            return 0;
        }

        plog("Database handler not found");
    }


    plog("Executing query:\n",$sql);

    #
    # Attempt to execute our prepared statement
    #
    $rc = $sth->execute;

    if (! defined( $rc ) )
    {
        #
        # We failed, print the error message for troubleshooting
        #

        plog("Unable to execute prepared SQL statement");
        plog("$DBI::errstr");

        return 0;
    }

    #
    # All is successful, return the statement handle and record
    #    
    
    return wantarray() ? ($sth,$rc) : {"_handler"=>$sth,"_result"=>$rc}


}
1;
