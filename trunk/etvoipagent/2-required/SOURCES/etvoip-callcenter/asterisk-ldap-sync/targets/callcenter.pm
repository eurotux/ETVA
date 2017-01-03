package targets::callcenter;
use strict;
use warnings;
use utf8;
use DBD::mysql;
use Data::Dumper;

our %MAP;

my $CCContacts;     # loaded contacts

sub new
{
	my ($class, %args) = @_;
	

        my $dbh = DBI->connect("DBI:mysql:database=$args{db};" .
                "host=$args{host};port=$args{port}",
                $args{user},
                $args{pass},
                {
                        RaiseError => 1,
                        FetchHashKeyName => 'NAME_lc',
                        #'mysql_enable_utf8'=> 1
                });

        #$dbh->do('SET NAMES utf8');

        my $m = {%MAP, %{$args{m} || {}}};

        bless { d => $dbh, m => $m, 'debug'=>$args{'debug'} }, $class;
}

sub prepare
{
        my ($self, $sql) = @_;

        $self->{d}->prepare($sql);
}

sub normalizeName
{
    my $self = shift;
    my ($name) = @_;
    $name =~ s/\s+/ /g;
    $name =~ s/;//g;
    $name =~ s/^\s+|\s+$//g;
    return $name;
}
sub load_users
{
    my $self = shift;
    my $s = $self->prepare(q{
    SELECT *
            FROM
                    contact
                    });
    $s->execute;

    #my $users = $s->fetchall_hashref('name');
    my %users = ();
    while( my $U = $s->fetchrow_hashref() ){
        my $name = $self->normalizeName($U->{'name'});
        if( !$users{"$name"} ){
            $users{"$name"} = [$U];
        } else {
            push(@{$users{"$name"}}, $U);
        }
    }
    $s->finish;

    print "[DEBUG] loadusers = ",Dumper(\%users),"\n" if( $self->{'debug'} );
    return {%users};
}
sub get_user
{
    my $self = shift;
    my ($user) = @_;

    if( !$CCContacts ){
        $CCContacts = $self->load_users();
    }

    my $nuser = $self->normalizeName($user);

    return $CCContacts->{$nuser};
}

sub add
{
    my $self = shift;
    my $u= shift;

	my $query = "INSERT INTO contact (" .
		(join ", ", (keys %{$self->{m}})) . 
		") VALUES (" .
		(join ", ", map "?", (keys %{$self->{m}})) . 
		")";

	my @attrs = map {
		defined($u->{"$_"}) ? $u->{"$_"} : 'NULL'
    } (keys %{$self->{m}});
    my $s = $self->{d}->do($query,undef,@attrs);

    # update users
    $CCContacts = $self->load_users();
}

sub modify
{
    my $self = shift;
    my ($old, $u, $rep) = @_;

	my $query = "UPDATE contact SET " .
		(join ", ", map "$_ = ?", (keys %$rep)) . 
		" WHERE id = " . $old->{'id'};

    my @attrs = values %$rep;

    my $s = $self->{d}->do($query,undef,@attrs);
    
    # update users
    $CCContacts = $self->load_users();
}

sub remove
{
    my $self = shift;
    my ($old) = @_;

	my $query = "DELETE contact " .
		" WHERE id = " . $old->{'id'};

    # do it
    my $s = $self->{d}->do($query);

    # update users
    $CCContacts = $self->load_users();
}

sub diffUsers
{
    my $self = shift;
    my ($N,$E) = @_;

    my %rep;
    while (my ($k, $v) = each %$N) {
        next if ref $v;
        my $c = $E->{$k};
        $v = "" unless defined $v;
        $c = "" unless defined $c;
        $rep{$k} = $v if ($v ne $c && $c ne 'NULL');
        #print "[DEBUG] diffUsers k=$k v=$v c=$c id=",$E->{'id'},"\n";
    }
    return %rep;
}
sub sync_users
{
	my $self = shift;
	my $users = shift;
	my $m = $self->{m};
	my $msub = $self->{msub};
	my $base = $self->{b};
	my $mysql = $self->{d};

    print "[DEBUG] sync_users debug=",$self->{'debug'},"\n";

	my %users;
	for my $u (values %$users) {
        #print "[DEBUG] dump=",Dumper($u),"\n";
		my %u = map { $_ => eval $m->{$_} } keys %$m;
        #print "[DEBUG] User name=",$u{'name'}," telefono=",$u{'telefono'},"\n";
        #print "[DEBUG] dump=",Dumper(\%u),"\n";

        my $n = [];
        while (my ($k, $v) = each %u) {
            if( ref($v) eq 'ARRAY' ){
                for(my $i=0; $i<scalar(@$v); $i++){
                    $n->[$i] = {} if( !$n->[$i] );
                    $n->[$i]{"$k"} = $v->[$i];
                }
            } else {
                $n->[0] = {} if( !$n->[0] );
                $n->[0]{"$k"} = $v;
            }
        }

        # fix other fields
        my @fields = ();
        @fields = keys %{$n->[0]} if( $n->[0] );
        for(my $i=1; $i<scalar(@$n); $i++){
            foreach my $k (@fields){
                $n->[$i]{"$k"} = $n->[0]{"$k"} if( !$n->[$i]{"$k"} );
            }
        }
        
        
        print "[DEBUG] NewUser=",Dumper($n),"\n";

		my $dn=$u{name};
		my $e = $self->get_user($dn);
		unless ($e) {
			#print "[DEBUG] new user: $dn\n";
			#warn "new user: $dn\n";
            foreach my $N (@$n){
                print "[DEBUG] New User name=",$N->{'name'}," telefono=",$N->{'telefono'},"\n";
                $self->add($N) if( !$self->{'debug'} );
            }
		} else {
            print "[DEBUG] OldUser=",Dumper($e),"\n";

            # get max of number of users
            my $nusers = ( scalar(@$n) > scalar(@$e) ) ? scalar(@$e): scalar(@$n);

            my $i = 0;  # index
            for($i=0; $i < $nusers; $i++){
                if( my %diff = $self->diffUsers($n->[$i],$e->[$i]) ){
                    my $N = $n->[$i];
                    print "[DEBUG] Modify User name=",$N->{'name'}," telefono=",$N->{'telefono'},"\n";
                    print "[DEBUG] Modify User `$dn' changed: ", join(", ",
                                                    sort keys %diff), "\n";
                    # update old user
                    $self->modify($e->[$i], $n->[$i], \%diff) if( !$self->{'debug'} );
                }
            }

            if( scalar(@$n) > scalar(@$e) ){    # more users
                for(;$i<scalar(@$n); $i++){
                    my $N = $n->[$i];
                    print "[DEBUG] Add User name=",$N->{'name'}," telefono=",$N->{'telefono'},"\n";
                    # add new user
                    $self->add($n->[$i]) if( !$self->{'debug'} );
                }
            } elsif( scalar(@$n) < scalar(@$e) ){   # less users
                for(;$i<scalar(@$e); $i++){
                    my $E = $e->[$i];
                    print "[DEBUG] Delete Old User name=",$E->{'name'}," telefono=",$E->{'telefono'},"\n";
                    # remove old users
                    $self->remove($e->[$i]) if( !$self->{'debug'} );
                }
            }
		}
	}

	return unless $self->{d};
	return; 
}

sub load_users_fromFile {
	my $file = shift();
	my %list;

	open(USERS, "<$file") or return({});
	while(my $u = <USERS>) {
		chomp($u);
		$list{$u} = undef;
	}
	close(USERS);
	return(\%list);
}

1;
