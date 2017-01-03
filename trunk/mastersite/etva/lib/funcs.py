
SECTION_GROUPS = 1
SECTION_USERS  = 2
SECTION_BACKUPS= 3
SECTION_PRODUCTS = 4

def userInGroup(user,groupName):
    if user:
        return user.groups.filter(name='Student').count() != 0
    return False
