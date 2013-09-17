#!/usr/bin/perl

=pod

=head1 NAME

VirtAgent::Disk - ...

=head1 SYNOPSIS

...

=head1 DESCRIPTION

...

=head1 METHODS

=over 4

=cut

package VirtAgent::Storage;

use strict;

BEGIN {
    # this is the worst damned warning ever, so SHUT UP ALREADY!
    $SIG{__WARN__} = sub { warn @_ unless $_[0] =~ /Use of uninitialized value/ };

    use VirtAgent;
    use vars qw($VERSION @ISA @EXPORT @EXPORT_OK %EXPORT_TAGS $CRLF);
    $VERSION = '0.0.1';
    @ISA = qw( VirtAgent );
    @EXPORT = qw( );
}

use ETVA::Utils;

=item ...

=cut

sub xml_parser_get_attr {
    my ($ch) = @_;
    my %A = ();
    if( my $attr = $ch->getAttributes() ){
        for(my $i=0;$i<$attr->getLength();$i++){
            my $n = $attr->item($i)->getNodeName();
            my $v = $attr->item($i)->getValue();
            $A{"$n"} = $v;
        }
    }
    return wantarray() ? %A : \%A;
}

sub xml_storage_pool_parser {

    my ($xml) = @_;

    my $parser = new XML::DOM::Parser();
    my $doc = $parser->parse($xml);
    my $root = $doc->getDocumentElement();

    my %SP = &xml_parser_get_attr($root);

    for my $ch ($root->getChildNodes()){
        my $nname = $ch->getNodeName();
        if( $nname eq 'name' || 
            $nname eq 'uuid' || 
            $nname eq 'allocation' ||
            $nname eq 'capacity' || 
            $nname eq 'available' 
            ){
            eval{ $SP{"$nname"} = $ch->getFirstChild->toString(); };
            if( $@ ){ $SP{"$nname"} = ""; }
        } elsif( $nname eq 'source' ){
            for my $cs ($ch->getChildNodes()){
                my $tn = $cs->getNodeName();
                if( $tn eq 'device' ){
                    # for device
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"device"} = [] if( !$SP{'source'}{"device"} );
                    push( @{$SP{'source'}{"device"}}, $at );
                    $SP{'source_device'} = [] if( !$SP{'source_device'} );
                    push( @{$SP{'source_device'}}, $at->{'path'} );
                } elsif( $tn eq 'host' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"host"} = $at;
                    $SP{'source_host'} = $at->{'name'};
                    $SP{'source_port'} = $at->{'port'} if( $at->{'port'} );
                } elsif( $tn eq 'dir' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"dir"} = $at;
                    $SP{'source_dir'} = $at->{'path'};
                } elsif( $tn eq 'directory' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"directory"} = $at;
                    $SP{'source_directory'} = $at->{'path'};
                } elsif( $tn eq 'adapter' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"adapter"} = $at;
                    $SP{'source_adapter'} = $at->{'name'};
                } elsif( $tn eq 'format' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"format"} = $at;
                    $SP{'source_format'} = $at->{'type'};
                } elsif( $tn eq 'name' ){
                    $SP{'source_name'} = $SP{'source'}{"name"} = $cs->getFirstChild->toString();
                }
                    
            }
        } elsif( $nname eq 'target' ){
            for my $cs ($ch->getChildNodes()){
                my $tn = $cs->getNodeName();
                if( $tn eq 'path' ){
                    $SP{'path'} = $SP{'target_path'} = $SP{'target'}{'path'} = $cs->getFirstChild->toString();
                } elsif( $tn eq 'permissions' ){
                    for my $cp ($cs->getChildNodes()){
                        my $pn = $cp->getNodeName();

                        # for mode, owner and group
                        if( $pn eq 'mode' ||
                            $pn eq 'owner' ||
                            $pn eq 'group' ||
                            $pn eq 'label' ){
                            $SP{"permissions_$pn"} = $SP{"target_permissions_$pn"} = $SP{'target'}{'permissions'}{"$pn"} = $cp->getFirstChild->toString();
                        }
                    }
                } elsif( $tn eq 'encryption' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'target'}{'encryption'} = $at;
                    $SP{'encryption_type'} = $SP{'target_encryption_type'} = $at->{'type'};
                    for my $ce ($cs->getChildNodes()){
                        my $en = $ce->getNodeName();
                        if( $en eq 'secret' ){
                            $SP{'target'}{'encryption'}{'secret'} = [] if( !$SP{'target'}{'encryption'}{'secret'} );
                            push(@{$SP{'target'}{'encryption'}{'secret'}},&xml_parser_get_attr($ce));
                        }
                    }
                }
            }
        }
    }

    # Avoid memory leaks - cleanup circular references for garbage collection
    $doc->dispose;

    return wantarray() ? %SP : \%SP;
}
sub xml_storage_pool_source_parser {

    my ($xml) = @_;

    my $parser = new XML::DOM::Parser();
    my $doc = $parser->parse($xml);
    my $root = $doc->getDocumentElement();

    my %Res = &xml_parser_get_attr($root);
    for my $ch ($root->getChildNodes()){
        my $nname = $ch->getNodeName();
        if( $nname eq 'source' ){
            my %SP = ();
            for my $cs ($ch->getChildNodes()){
                my $tn = $cs->getNodeName();
                if( $tn eq 'device' ){
                    # for device
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"device"} = [] if( !$SP{'source'}{"device"} );
                    push( @{$SP{'source'}{"device"}}, $at );
                    $SP{'source_device'} = [] if( !$SP{'source_device'} );
                    push( @{$SP{'source_device'}}, $at->{'path'} );
                } elsif( $tn eq 'host' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"host"} = $at;
                    $SP{'source_host'} = $at->{'name'};
                    $SP{'source_port'} = $at->{'port'} if( $at->{'port'} );
                } elsif( $tn eq 'dir' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"dir"} = $at;
                    $SP{'source_dir'} = $at->{'path'};
                } elsif( $tn eq 'directory' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"directory"} = $at;
                    $SP{'source_directory'} = $at->{'path'};
                } elsif( $tn eq 'adapter' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"adapter"} = $at;
                    $SP{'source_adapter'} = $at->{'name'};
                } elsif( $tn eq 'format' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SP{'source'}{"format"} = $at;
                    $SP{'source_format'} = $at->{'type'};
                } elsif( $tn eq 'name' ){
                    $SP{'source_name'} = $SP{'source'}{"name"} = $cs->getFirstChild->toString();
                }
            }
            if( %SP ){
                $Res{'sources'} = [] if( !$Res{'sources'} );
                push( @{$Res{'sources'}}, \%SP );
            }
        }
    }

    # Avoid memory leaks - cleanup circular references for garbage collection
    $doc->dispose;

    return wantarray() ? %Res : \%Res;
}

sub gen_xml_storage_pool_source {
    my $self = shift;
    my (%p) = @_;
    my $X = XML::Generator->new(':pretty');

    my @source_params = $self->get_storage_pool_source_xml(%p);
    return sprintf('%s', $X->source( @source_params ));
}
sub get_storage_pool_source_xml {
    my $self = shift;
    my (%p) = @_;

    my $X = XML::Generator->new(':pretty');

    my $source = $p{'source'};

    my @source_params = ();
    for my $sk (keys %$source){
        my $sh = $source->{"$sk"};
        if( $sk eq 'device' ){
            if( ref($sh) eq 'ARRAY' ){
                push(@source_params, map { $X->device( getAttrs($_) ) } @$sh );
            } else {
                push(@source_params, $X->device( getAttrs($sh) ) );
            }
        } elsif( $sk eq 'directory' ||
                    $sk eq 'dir' || 
                    $sk eq 'adapter' || 
                    $sk eq 'host' ||
                    $sk eq 'format' ){
            push(@source_params, $X->${sk}( getAttrs($sh) ) );
        } elsif( $sk eq 'name' ){
            push(@source_params, $X->name( $sh ) );
        }
    }
    return wantarray() ? @source_params : \@source_params;
}
sub gen_xml_storage_pool {
    my $self = shift;
    my (%p) = @_;

    my $X = XML::Generator->new(':pretty');

    my @pool_params = ();
    push(@pool_params, {'type'=>$p{'type'}});

    push(@pool_params, $X->name( $p{'name'} ));
    push(@pool_params, $X->uuid( $p{'uuid'} )) if( $p{'uuid'} );
    push(@pool_params, $X->allocation( $p{'allocation'} )) if( defined $p{'allocation'} );
    push(@pool_params, $X->capacity( $p{'capacity'} )) if( defined $p{'capacity'} );
    push(@pool_params, $X->available( $p{'available'} )) if( defined $p{'available'} );

    if( my $source = $p{'source'} ){
        my @source_params = $self->get_storage_pool_source_xml(%p);
        push( @pool_params, $X->source( @source_params )) if( @source_params );
    }
    
    if( my $target = $p{'target'} ){
        my @target_params = ();
        for my $tk (keys %$target){
            my $th = $target->{"$tk"};
            if( $tk eq 'path' ){
                push(@target_params, $X->path( $th ));
            } elsif( $tk eq 'permissions' ){
                my @permissions_params = ();
                for my $pk (keys %$th){
                    my $ph = $th->{"$pk"};
                    push( @permissions_params, $X->${pk}($ph) );
                }
                push(@target_params, @permissions_params) if( @permissions_params );
            } elsif( $tk eq 'encryption' ){
                my @encryption_params = ( 'type'=>$th->{'type'} );
                if( $th->{'secret'} ){
                    push(@encryption_params, $X->secret( getAttrs($th->{'secret'}) ));
                }
                push(@target_params, $X->encryption( @encryption_params ));
            }
        }
        push(@pool_params,$X->target( @target_params )) if( @target_params );
    }

    return sprintf('%s', $X->pool(@pool_params) );
}

sub xml_storage_volume_parser {

    my ($xml) = @_;

    my $parser = new XML::DOM::Parser();
    my $doc = $parser->parse($xml);
    my $root = $doc->getDocumentElement();

    my %SV = &xml_parser_get_attr($root);

    for my $ch ($root->getChildNodes()){
        my $nname = $ch->getNodeName();
        if( $nname eq 'name' || 
            $nname eq 'key' ){
            eval{ $SV{"$nname"} = $ch->getFirstChild->toString(); };
            if( $@ ){ $SV{"$nname"} = ""; }
        } elsif( $nname eq 'capacity' ||
                    $nname eq 'allocation' ){
            my %at = &xml_parser_get_attr($ch);
            my $vn = $ch->getFirstChild->toString();
            my $u = $at{'unit'};
            $SV{"$nname"} = "${vn}${u}";
        } elsif( $nname eq 'source' ){
            for my $cs ($ch->getChildNodes()){
                my $tn = $cs->getNodeName();
                if( $tn eq 'device' ){
                    # for device
                    my $at = &xml_parser_get_attr($cs);
                    my %D = ( %$at );
                    for my $cd ($cs->getChildNodes()){
                        my $dn = $cd->getNodeName();
                        if( $dn eq 'extent' ){
                            $D{'extent'} = &xml_parser_get_attr($cd);
                            last;
                        }
                    }
                    $SV{'source'}{"device"} = [] if( !$SV{'source'}{"device"} );
                    push( @{$SV{'source'}{"device"}}, \%D );
                } elsif( $tn !~ m/^#/ ){
                    # for other cases: host, dir,  ...
                    $SV{'source'}{"$tn"} = &xml_parser_get_attr($cs);
                }
                    
            }
        } elsif( $nname eq 'target' ){
            for my $cs ($ch->getChildNodes()){
                my $tn = $cs->getNodeName();
                if( $tn eq 'path' ){
                    $SV{'path'} = $SV{'target_path'} = $SV{'target'}{'path'} = $cs->getFirstChild->toString();
                } elsif( $tn eq 'format' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SV{'target'}{'format'} = $at;
                    $SV{'format'} = $SV{'target_format'} = $at->{'type'};
                } elsif( $tn eq 'permissions' ){
                    for my $cp ($cs->getChildNodes()){
                        my $pn = $cp->getNodeName();

                        # for mode, owner and group
                        if( $pn eq 'mode' ||
                            $pn eq 'owner' ||
                            $pn eq 'group' ||
                            $pn eq 'label' ){
                            $SV{"permissions_$pn"} = $SV{"target_permissions_$pn"} = $SV{'target'}{'permissions'}{"$pn"} = $cp->getFirstChild->toString();
                        }
                    }
                }
            }
        } elsif( $nname eq 'backingStore' ){
            for my $cs ($ch->getChildNodes()){
                my $tn = $cs->getNodeName();
                if( $tn eq 'path' ){
                    $SV{'backingStore_path'} = $SV{'backingStore'}{'path'} = $cs->getFirstChild->toString();
                } elsif( $tn eq 'format' ){
                    my $at = &xml_parser_get_attr($cs);
                    $SV{'backingStore'}{'format'} = $at;
                    $SV{'backingStore_format'} = $at->{'type'};
                } elsif( $tn eq 'permissions' ){
                    for my $cp ($cs->getChildNodes()){
                        my $pn = $cp->getNodeName();

                        # for mode, owner and group
                        if( $pn eq 'mode' ||
                            $pn eq 'owner' ||
                            $pn eq 'group' ||
                            $pn eq 'label' ){
                            $SV{"backingStore_permissions_$pn"} = $SV{'backingStore'}{'permissions'}{"$pn"} = $cp->getFirstChild->toString();
                        }
                    }
                }
            }
        }
    }

    # Avoid memory leaks - cleanup circular references for garbage collection
    $doc->dispose;

    return wantarray() ? %SV : \%SV;
}

sub gen_xml_storage_volume {
    my $self = shift;
    my (%p) = @_;

    my $X = XML::Generator->new(':pretty');

    my @volume_params = ();

    push(@volume_params, $X->name( $p{'name'} ));
    push(@volume_params, $X->key( $p{'key'} ));
    if( defined $p{'allocation'} ){
        my ($v,$u) = ( $p{'allocation'} =~ m/(\d+)([KkMmGgTtPpEe])?/ );
        my @ap = ();
        push(@ap, {'unit'=>$u} ) if( $u );
        push(@ap,$v);
        push(@volume_params, $X->allocation( @ap ));
    }
    if( defined $p{'capacity'} ){
        my ($v,$u) = ( $p{'capacity'} =~ m/(\d+)([KkMmGgTtPpEe])?/ );
        my @ap = ();
        push(@ap, {'unit'=>$u} ) if( $u );
        push(@ap,$v);
        push(@volume_params, $X->capacity( @ap ));
    }

    if( my $source = $p{'source'} ){
        my @source_params = ();
        for my $sk (keys %$source){
            my $sh = $source->{"$sk"};
            if( $sk eq 'device' ){
                if( ref($sh) eq 'ARRAY' ){
                    push(@source_params, map { $X->device( getAttrs($_) ) } @$sh );
                } else {
                    push(@source_params, $X->device( getAttrs($sh) ) );
                }
            } elsif( $sk eq 'directory' ||
                        $sk eq 'dir' || 
                        $sk eq 'adapter' || 
                        $sk eq 'host' ||
                        $sk eq 'format' ){
                push(@source_params, $X->${sk}( getAttrs($sh) ) );
            } elsif( $sk eq 'name' ){
                push(@source_params, $X->name( $sh ) );
            }
        }
        push( @volume_params, $X->source( @source_params )) if( @source_params );
    }
    
    if( my $target = $p{'target'} ){
        my @target_params = ();
        for my $tk (keys %$target){
            my $th = $target->{"$tk"};
            if( $tk eq 'path' ){
                push(@target_params, $X->path( $th ));
            } elsif( $tk eq 'format' ){
                push(@target_params, $X->format( getAttrs($th) ));
            } elsif( $tk eq 'permissions' ){
                my @permissions_params = ();
                for my $pk (keys %$th){
                    my $ph = $th->{"$pk"};
                    push( @permissions_params, $X->${pk}($ph) );
                }
                push(@target_params, @permissions_params) if( @permissions_params );
            }
        }
        push(@volume_params,$X->target( @target_params )) if( @target_params );
    }

    if( my $backingStore = $p{'backingStore'} ){
        my @backingStore_params = ();
        for my $tk (keys %$backingStore){
            my $th = $backingStore->{"$tk"};
            if( $tk eq 'path' ){
                push(@backingStore_params, $X->path( $th ));
            } elsif( $tk eq 'format' ){
                push(@backingStore_params, $X->format( getAttrs($th) ));
            } elsif( $tk eq 'permissions' ){
                my @permissions_params = ();
                for my $pk (keys %$th){
                    my $ph = $th->{"$pk"};
                    push( @permissions_params, $X->${pk}($ph) );
                }
                push(@backingStore_params, @permissions_params) if( @permissions_params );
            }
        }
        push(@volume_params,$X->backingStore( @backingStore_params )) if( @backingStore_params );
    }

    return sprintf('%s', $X->volume(@volume_params) );
}

1;

=back

=pod

=head1 BUGS

...

=head1 AUTHORS

...

=head1 COPYRIGHT

...

=head1 LICENSE

...

=head1 SEE ALSO

L<VirtAgentInterface>, L<VirtAgent::Disk>, L<VirtAgent::Network>,
L<VirtMachine>
C<http://libvirt.org>


=cut

=pod

