#!/usr/bin/perl
# agent-logoff-alert
#   alert when agent logoff to much time

use strict;

use lib '/usr/local/sap2elastix';

use utilcommon;
use utilsql;

use POSIX;
use Data::Dumper;

sub ignoreAgent {
    my ($A) = @_;
    return 1 if( $A->{'name'} eq 'fapg' );
    return 1 if( $A->{'name'} eq 'cmar' );
    return 1 if( $A->{'name'} eq 'cmar-laptop' );
    return 0;
}

sub ignoreAgentLogOff {
    my ($A) = @_;
    return 1 if( $A->{'name'} eq 'fapg' );
    return 1 if( $A->{'name'} eq 'cmar' );
    return 1 if( $A->{'name'} eq 'cmar-laptop' );
    return 1 if( $A->{'name'} eq 'pjs' );
    return 0;
}

sub calcAlertByLogOff {
    my ($A,%conf) = @_;


}
sub calcAlertByBreak {
    my ($A,%conf) = @_;

    if( $A ){
        my $ntime = $conf{'report'}{'default_alert_time'} || 35;   # default 35 minutes

        if( ($A->{'break'} eq 'Almoço')  ){
            $ntime = $conf{'report'}{'lunch_alert_time'} || 90;     # default 90 minutes
        }
        my $alertdate = strftime('%Y-%m-%d %H:%M:%S', localtime(time() - $ntime*60));

        if( !$A->{'datetime_end'} && $A->{'datetime_init'} &&
                            ($A->{'datetime_init'} le $alertdate) ){
            return $ntime;
        }
    }

    return 0;
}

# reload asterisk
sub main {
    my %conf = utilcommon::load_conf('/usr/local/sap2elastix/config.conf');
    my ($dbhost,$dbuser,$dbpass) = ($conf{'mysql'}{'host'} || "127.0.0.1",$conf{'mysql'}{'user'} || "sap", $conf{'mysql'}{'pass'} || "123456");

    my $debug = $conf{'report'}{'debug'} || 0;

    my $ntime = $conf{'report'}{'default_alert_time'} || 35;   # default 35 minutes

    my $datelimit_i = strftime('%Y-%m-%d 08:00:00', localtime(time()));
    my $datelimit_e = strftime('%Y-%m-%d 17:00:00', localtime(time()));     # end of alerts for log-off

    my $dlimit_breaks_e = strftime('%Y-%m-%d 19:05:00', localtime(time()));  # end of alerts for breaks

    my $nowtime = strftime('%Y-%m-%d %H:%M:%S', localtime(time()));     # now time

    my $alertdate = strftime('%Y-%m-%d %H:%M:%S', localtime(time() - $ntime*60));

    my $ccDBH = utilsql::sqlConnect("DBI:mysql:database=call_center;host=$dbhost", $dbuser, $dbpass);

    my @report_lines = ();
    # get agents
    if( my $c = utilsql::sqlSelect($ccDBH
                    ,"agent.id, agent.type, agent.number, agent.name, SUM(TIME_TO_SEC(duration)) AS total_login_time"
                    ,"agent" 
                        . " LEFT JOIN audit"
                            . " ON agent.id = audit.id_agent "
                            . " AND audit.datetime_init > '$datelimit_i'"
                            . " AND audit.id_break IS NULL "
                    ,"estatus = 'A'"
                    ,"GROUP BY agent.id" ) ){
        while(my $A = $c->fetchrow_hashref()){
            my $agent_id = $A->{'id'};
            my $agent_name = $A->{'name'};

            next if( &ignoreAgent($A) );    # Ignore some agents

            print "DEBUG ","agent '$A->{'name'}' ('$A->{'number'}'/'$A->{'id'}') total_login_time=$A->{'total_login_time'}","\n" if( $debug );

            if( !&ignoreAgentLogOff($A) ){  # Ignore some agents log off

                if( $nowtime lt $datelimit_e ){     # is not end of day
                    if( my $c1 = utilsql::sqlSelect($ccDBH,
                                                "datetime_init, datetime_end",
                                                "audit",
                                                "id_agent = '$agent_id' AND id_break IS NULL AND datetime_init > '$datelimit_i'",
                                                "ORDER BY datetime_init DESC LIMIT 0,1") ){
                        my ($di,$de) = $c1->fetchrow();
                        if( $de && ($de le $alertdate) ){
                            print "DEBUG ","Agent '$A->{'name'}' session logoff more than '$ntime' minutes ($de).","\n" if( $debug );
                            push @report_lines, "Agent '$A->{'name'}' session logoff more than '$ntime' minutes ($de).","\n";
                        }
                        $c1->finish();
                    }
                }
            }

            if( $nowtime lt $dlimit_breaks_e ){     # is not end of day for breaks
                # TODO check breaks types
                if( my $c2 = utilsql::sqlSelect($ccDBH,
                                            "datetime_init, datetime_end, break.name as break",
                                            "audit, break",
                                            "id_agent = '$agent_id' AND id_break IS NOT NULL AND id_break=break.id AND datetime_init > '$datelimit_i'",
                                            "ORDER BY datetime_init DESC LIMIT 0,1") ){
                    if( my $B = $c2->fetchrow_hashref() ){
                        if( my $bntime = &calcAlertByBreak($B, %conf) ){
                            print "DEBUG ","Agent '$A->{'name'}' is in break '$B->{'break'}' more than '$bntime' minutes ($B->{'datetime_init'}).","\n" if( $debug );
                            push @report_lines, "Agent '$A->{'name'}' is in break '$B->{'break'}' more than '$bntime' minutes ($B->{'datetime_init'}).","\n";
                        }
                    }
                    $c2->finish();
                }
            }
        }
        $c->finish();
    }
    utilsql::sqlDisconnect($ccDBH);

    if( @report_lines ){
        print "DEBUG ","report_lines @report_lines","\n" if( $debug );
        utilcommon::sendEmail( 'debug'=>$debug,
                    'from' => $conf{'report'}{'from'} || 'etvoip@eurotux.com',
                    'to'   => $conf{'report'}{'to'} || 'cmar@eurotux.com',
                    'subject' => 'ETVOIP: Alert sessions',
                    'msg'     => <<__MSG__
Hello,

The following sessions have referred alerts:

@report_lines

__MSG__
                    );
    }
}

&main(@ARGV);

1;

