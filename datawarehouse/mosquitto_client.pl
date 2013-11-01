#!/usr/bin/perl

use strict;
use warnings;
use FileHandle;
use JSON qw( decode_json ); # libjson-perl
use WebSphere::MQTT::Client; # perl -MCPAN -e "install WebSphere::MQTT::Client"

my %CFG = (
	'MQTT_sub'				=> '/usr/bin/mosquitto_sub',
	'MQTT_port'				=> 1883,
	'MQTT_host'				=> 'guru',
	'MQTT_topic'			=> '/couleurs/#',
	#'MQTT_topic'			=> '/test/#',
	'MQTT_qos'				=> 2,
	'MQTT_identifier'		=> 'trigger-client',
	'MQTT_Clean_start'		=> 0,
	'MQTT_Async'			=> 1,
	'MQTT_Debug'			=> 0,
	'MQTT_keep_alive'		=> 10,
	'MQTT_retry_count'		=> 10,
	'MQTT_retry_interval'	=> 10,
	
	'API_cli'				=> '/usr/bin/php /var/www/simple-dashboard/API/index.php'
);



local $| = 1;
open (PID, ">", "pid");
print PID $$;
close PID;
open(PID, "pid");
$SIG{INT} = "breakPrg";
#$SIG{__DIE__} = sub { breakPrg };
#$SIG{HUP} = sub { breakPrg };

# Get Triggers from API
my $triggersJson = `$CFG{'API_cli'} --action=getTriggers --filter_enable=true`;
#print "$CFG{'API_cli'} --action=getTriggers --filter_enable=true";

my $decoded = decode_json($triggersJson);
$decoded = $decoded->{"getTriggers"};
#use Data::Dumper;
#print Dumper($decoded);

my @triggers = ();
foreach my $t ( @{ $decoded } ) {
	push(@triggers, $t);
}
print "\t". @triggers . " TRIGGERS\n";

# Connect to Broker
my $mqtt = new WebSphere::MQTT::Client(
	Hostname		=> $CFG{'MQTT_host'},
	Port			=> $CFG{'MQTT_port'},
	clientid		=> $CFG{'MQTT_identifier'},
	Clean_start		=> $CFG{'MQTT_Clean_start'},
	Async			=> $CFG{'MQTT_Async'},
	Debug			=> $CFG{'MQTT_Debug'},
	keep_alive		=> $CFG{'MQTT_keep_alive'},
	retry_count		=> $CFG{'MQTT_retry_count'},
	retry_interval	=> $CFG{'MQTT_retry_interval'},
	persist			=> undef
);
print "\tCONNECTING Mqtt TO ".$CFG{'MQTT_host'};
# Connect to Broker
my $res = $mqtt->connect();
print "\tOK\n";
 
# Subscribe to topic
$res = $mqtt->subscribe( $CFG{'MQTT_topic'}, $CFG{'MQTT_qos'} );
print "\tSUBSCRIBED TO ".$CFG{'MQTT_topic'}."\n";

# Get Messages
while( 1 ) {
	my @res = $mqtt->receivePub();
	checkAllTriggers($res[0], $res[1]);
	#errors can be caught by eval { }
}

sub breakPrg {
	# Clean up
	$mqtt->terminate();
	if (unlink("pid")) {  }
	exit;
}

sub checkAllTriggers {
	my($topic, $content, $timestamp, $value) = @_;
	my @vals = split(/ /, rtrim($content));
	$timestamp = $vals[0];
	$value = $vals[1];
	
	print "\tRECEIVED MSG on " . $topic . " : " . $value . " (" . scalar(localtime($timestamp)) . ")\n" if ($value);
	 
	foreach my $trigger (@triggers) {
		$trigger->{"previousTimestamp"} = defined($trigger->{"previousTimestamp"}) ? $trigger->{"previousTimestamp"} : 0;
		
		if ( defined($trigger->{"previousValue"}) ) {
			if ( $trigger->{"previousValue"} > $trigger->{"minthreshold"} && # Argument "" isn't numeric in numeric lt (<)
				 $trigger->{"previousValue"} < $trigger->{"maxthreshold"} ) {
					$trigger->{"previousValueInside"} = 1;
			} elsif ( $trigger->{"previousValue"} > $trigger->{"maxthreshold"} ||
				 $trigger->{"previousValue"} < $trigger->{"minthreshold"} ) {
					$trigger->{"previousValueInside"} = 0;
			}
		}
		$trigger->{"previousValueInside"} = defined($trigger->{"previousValueInside"}) ? $trigger->{"previousValueInside"} : 0;
		#print $trigger->{"previousValueInside"}."<--\n";
		
		if ( $topic =~ $trigger->{"topic"} && $timestamp ne $trigger->{"previousTimestamp"} ) {
			my $eventTriggered = 0;
			if (	$trigger->{"event"} eq "onUpper" &&
					$trigger->{"maxthreshold"} &&
					$value > $trigger->{"maxthreshold"}
				) {
					$eventTriggered = 1;
				
			} elsif (
					$trigger->{"event"} eq "onLower" &&
					$trigger->{"minthreshold"} &&
					$value < $trigger->{"minthreshold"}
				) {
					$eventTriggered = 1;
				
			} elsif (
					$trigger->{"event"} eq "onEnter" &&
					$trigger->{"maxthreshold"} &&
					$trigger->{"minthreshold"} && 
					$value > $trigger->{"minthreshold"} &&
					$value < $trigger->{"maxthreshold"} &&
					$trigger->{"previousValueInside"} == 0
				) {
					$eventTriggered = 1;
					$trigger->{"previousValueInside"} = 1;
				
			} elsif (
					$trigger->{"event"} eq "onLeave" &&
					$trigger->{"maxthreshold"} &&
					$trigger->{"minthreshold"} &&
					$trigger->{"previousValueInside"} == 1 &&
					( $value < $trigger->{"minthreshold"} ||
					$value > $trigger->{"maxthreshold"} )
				) {
					$eventTriggered = 1;
					$trigger->{"previousValueInside"} = 0;
				
			} elsif (
					$trigger->{"event"} eq "onIncrease" &&
					$trigger->{"previousValue"} &&
					$trigger->{"previousValue"} < $value
				) {
					$eventTriggered = 1;
				
			} elsif ( 
					$trigger->{"event"} eq "onDecrease" &&
					$trigger->{"previousValue"} &&
					$trigger->{"previousValue"} > $value
				) {
					$eventTriggered = 1;
			}
			
			# ok, request action to be triggered
			if ( $eventTriggered eq 1 ) {
				print "\t   ALERT! ".$topic." (current=".$value." - ".$trigger->{"event"}." - [".$trigger->{"minthreshold"}."-".$trigger->{"maxthreshold"}."])" . " (trigger_id=" . $trigger->{"trigger_id"} . ")\n";
				
				my $content = decode_json( callForAction($trigger->{"event"}, $trigger->{"trigger_id"}, $timestamp, $value, $trigger->{"previousValue"}, $CFG{'API_cli'}) );
				if ( $content->[0]->{"status"} ne "ok" ) {
					$trigger->{"previousTimestamp"} = 123456; # permet de redéclencher le trigger au prochain receivePub
				}
				print "\tACTION STATUS=".$content->[0]->{"status"}."\n";

				if ( $trigger->{"logEventToFlow_id"} ) {
					my $content = decode_json( setData($trigger->{"logEventToFlow_id"}, $timestamp, $value, $CFG{'API_cli'}) );
				}
				
			} else {
				print "\tNO ALERT! ".$topic." (current=".$value." - ".$trigger->{"event"}." - [".$trigger->{"minthreshold"}."-".$trigger->{"maxthreshold"}."])\n";
			}

			$trigger->{"previousValue"} = $value;
			if ( $trigger->{"previousTimestamp"} ne 123456 ) {
				$trigger->{"previousTimestamp"} = $timestamp;
			}
		}
	}
}

sub rtrim($) {
	my $string = shift;
	$string =~ s/\s+$//;
	return $string;
}

sub callForAction {
	my($event, $trigger_id, $timestamp, $value, $previousValue, $API_cli) = @_;
	my $response = `$API_cli --action=triggerAction --trigger_id=$trigger_id --value=$value --previousValue=$previousValue --timestamp=$timestamp`;
	return $response;
}

sub setData {
	my($flow_id, $timestamp, $value, $API_cli) = @_;
	my $response = `$API_cli --action=setData --flow_id=$flow_id --timestamp=$timestamp --value=$value`;
	return $response;
}


