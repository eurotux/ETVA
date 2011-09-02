all: smb ent

smb:
	@tar zcf smb.tgz etc.smb --exclude=.svn
ent:
	@tar zcf ent.tgz etc.ent --exclude=.svn
distclean:
	@rm -f smb.tgz ent.tgz
