#!/usr/bin/perl

package utilsql;

use strict;

use DBI;

# Connect
sub sqlConnect {
    my ($dsn, $dbuser, $dbpass) = @_;

    # connect
    my $DBH = DBI->connect($dsn, $dbuser, $dbpass);

    if (!$DBH) {                                # give-up on database
        die "Couldn't SQL Connect ($DBI::errstr)"."\n";
    }

    return $DBH;
}

# prepare Where
sub sqlWhere {
    sub sqlWhereHash {
        my (%where) = @_;

        my $s_where = '';
        foreach my $field (keys %where){
            my $value = $where{"$field"};
            $s_where .= ' AND ' if( $s_where );
            $s_where .= "$field = '$value'";
        }
        return $s_where;
    }
    sub sqlWhereArray {
        my (@where) = @_;

        my $s_where = '';
        foreach my $subquery (@where){
            $s_where .= ' OR ' if( $s_where );
            $s_where .= &sqlWhere($subquery);
        }
        return $s_where;
    }
    my ($where) = @_;
    if( ref($where) eq 'HASH' ){
        $where = &sqlWhereHash(%$where);
    } elsif( ref($where) eq 'ARRAY' ){
        $where = &sqlWhereArray(@$where);
    }
    return $where;
}

# Select
sub sqlSelect {
    my ($DBH, $select, $from,  $where, $other ) = @_;

    # prepare where
    $where = &sqlWhere($where);

    my $sql = "SELECT $select";
    $sql .= " FROM $from" if( $from );
    $sql .= " WHERE $where" if( $where );
    $sql .= " $other" if( $other );

    # prepare execution
    my $c = $DBH->prepare($sql);

    #  execut query
    my $ok = $c->execute;

    if( !$ok ){
        $c->finish();
        $c = undef;
    }
    
    return $c;
}

# Insert
sub sqlInsert {
    my ($DBH, $table, $data) = @_;

    # get names of fields and values
    my $names = join(",", keys %$data);
    my $values = join(",", map { $DBH->quote($_) } values %$data);

    # prepare INSERT
    my $sql = "INSERT INTO $table ($names) VALUES($values)";

    #  do Insert
    return $DBH->do($sql);
}

# Delete
sub sqlDelete {
    my ($DBH, $from, $where, $other) = @_;

    # prepare where
    $where = &sqlWhere($where);

    # prepare DELETE
    my $sql = "DELETE ";
    $sql .= "FROM $from " if( $from );
    $sql .= "WHERE $where " if( $where );
    $sql .= "$other" if( $other );

    # do Delete
    my $res = $DBH->do($sql);
    return $res;
}

# Update
sub sqlUpdate {
    my ($DBH, $table, $data, $where) = @_;

    # prepare where
    $where = &sqlWhere($where);

    # prepare Set of fields
    my @set_fields = ();
    foreach my $field (keys %$data){
        my $value = $data->{"$field"};
        my $line = "$field = ".$DBH->quote($value);
        push(@set_fields, $line);
    }

    # prepare Update
    my $sql = "UPDATE $table SET ";
    $sql .= join(",", @set_fields);
    $sql .= "WHERE $where " if( $where );

    # do Update
    return $DBH->do($sql);
}

# Disconnect
sub sqlDisconnect {
    my ($DBH) = @_;

    $DBH->disconnect();
    undef $DBH;

    return $DBH;
}

1;
