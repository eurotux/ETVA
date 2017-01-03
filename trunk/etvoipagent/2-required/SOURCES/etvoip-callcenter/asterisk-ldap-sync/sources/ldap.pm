package sources::ldap;
use strict;
use warnings;
use Net::LDAP;
use Net::LDAP::Control::Paged;
use Data::Dumper;
use Digest::SHA1 'sha1_base64';
use MIME::Base64;
use YAML;

our %MAP = ();

sub apply_map
{
	my ($self, $u) = @_;


	my %m = %{$self->{m}};
#    my %ola = map { @{$_->{'cn'}} => { 'telephonenumber' => $_->{'telephonenumber'}, 'mobile' => $_->{'mobile'}  } } (values %{$res->as_struct});

	while (my ($k, $v) = each %m) {
		unless (ref $v) {
			$u->{$k} = eval $v;
		} elsif (ref($v) eq "CODE") {
			$u->{$k} = $v->($u);
		} else {
			die "can't convert using ", ref($v), "\n";
		}
	}
}

sub new
{
	my ($class, %args) = @_;


    my $ldap = new Net::LDAP 'ldap.dmz.eurotux.local',
        version => 3,
        debug => 0,
        sizelimit => 3000,
        timelimit=>10
            or die "couln't create ldap object: $!\n";

	my $m = {%MAP, %{$args{m} || {}}};
	bless { d => $ldap, m => $m, 'debug'=>$args{'debug'} }, $class;
}

sub users
{
	my $self = shift;

    print "[DEBUG] users debug=",$self->{'debug'},"\n" if( $self->{'debug'} );

    my $res = $self->{d}->search(
        base => 'ou=contactos,dc=eurotux,dc=com',
        scope=>'sub',
        filter=>"(|(mobile=*)(telephoneNumber=*))",
        attrs => [qw(cn mobile telephoneNumber homePhone mail o)],
    );

    die "couldn't perform search: ", $res->error, "\n" if $res->code;

    my @entries = $res->entries;
    print "[DEBUG] entries users count=",scalar(@entries),"\n";
    #print "[DEBUG] DUMP=",Dumper(\@entries),"\n";
    return undef if @entries == 0;

	# pjs> importante email pode ter valores repetidos por
	#      isso nao pode ser usado como chave da hash
	#my $users = $s->fetchall_hashref('email');
	my $users = $res->as_struct;

	map { $self->apply_map($_) } (values %$users);
	map { my $nkey = $users->{$_}->{name} . " - " . ($users->{$_}->{company} ? $users->{$_}->{company} : ""); $users->{$nkey} = delete($users->{$_}) } (keys %$users);

	return $users;
}

1;
