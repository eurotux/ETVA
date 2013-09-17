using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

using System.DirectoryServices.AccountManagement;


namespace primaveraconsole
{
    class WindowsUsers
    {
        private PrincipalContext oPrincipalContext;
        public WindowsUsers()
        {
            oPrincipalContext = new PrincipalContext(ContextType.Machine, Environment.MachineName);
        }
        public void list_users(string groups = "Users")
        {
            if (groups == "") groups = "Users";

            string[] groups_list = groups.Split(new char[] { ',' });

            foreach (string group in groups_list)
            {
                Common.WriteToConsoleOut("Group: " + group);

                GroupPrincipal oGroupPrincipal = GroupPrincipal.FindByIdentity(oPrincipalContext, group);
                PrincipalSearchResult<Principal> oPrincipalSearchResultMembers = oGroupPrincipal.GetMembers();
                foreach (Principal oResultMember in oPrincipalSearchResultMembers)
                {
                    Common.WriteToConsoleOut(" User: " + oResultMember.Name);
                }
            }
        }
        public void get_user(string username)
        {
            UserPrincipal oUserPrincipal = GetUser(username);

            if (oUserPrincipal == null)
            {
                Common.WriteToConsoleOut("User '" + username + "' not found.");
            }
            else
            {
                Common.WriteToConsoleOut("Name: " + oUserPrincipal.SamAccountName);

                PrincipalSearchResult<Principal> oPrincipalSearchResult = oUserPrincipal.GetGroups();
                foreach (Principal oResult in oPrincipalSearchResult)
                {
                    Common.WriteToConsoleOut(" Group: " + oResult.Name);
                }
            }

        }
        public bool create_user(string username, string password, string groups = "Users")
        {
            UserPrincipal oUserPrincial = GetUser(username);
            if (oUserPrincial == null)
            {
                try
                {
                    UserPrincipal newUser = CreateUser(username, password);

                    if (groups == "")
                    {
                        AddGroup(newUser, "Users");
                    }
                    else
                    {
                        string[] groups_list = groups.Split(',');
                        if (groups_list.Length > 0)
                        {
                            foreach (string group in groups_list)
                            {
                                AddGroup(newUser, group);
                            }
                        }
                    }
                    Common.WriteToConsoleOut("User '" + username + "' created with success.");
                    return true;
                }
                catch (PasswordException e)
                {
                    Common.WriteToConsoleOut("Cannot create user '" + username + "'. Invalid password: {0}", e.Message);
                }
                catch (Exception e)
                {
                    Common.WriteToConsoleOut("Cannot create user '" + username + "': {0}", e);
                }
            }
            else
            {
                Common.WriteToConsoleOut("User '" + username + "' already created.");
            }
            return false;
        }
        private UserPrincipal GetUser(string Username)
        {
            UserPrincipal up = UserPrincipal.FindByIdentity(oPrincipalContext, IdentityType.SamAccountName, Username);
            return up;
        }
        private UserPrincipal CreateUser(string Username, string Password)
        {
            UserPrincipal oUserPrincipal = new UserPrincipal(oPrincipalContext, Username, Password, true);
            oUserPrincipal.UserCannotChangePassword = false;
            oUserPrincipal.PasswordNeverExpires = false;
            oUserPrincipal.Save(); // this is where it crashes when I run through the debugger
            return oUserPrincipal;
        }
        private void AddGroup(UserPrincipal Up, string GroupName)
        {
            GroupPrincipal gp = GroupPrincipal.FindByIdentity(oPrincipalContext, GroupName);
            if (!gp.Members.Contains(Up))
            {
                gp.Members.Add(Up);
                gp.Save();
            }
            gp.Dispose();
        }
        //private void CreateProfile(UserPrincipal Up)
        //{
        //    int MaxPath = 240;
        //    StringBuilder pathBuf = new StringBuilder(MaxPath);
        //    uint pathLen = (uint)pathBuf.Capacity;
        //    //int Res = CreateProfile(Up.Sid.ToString(), Up.SamAccountName, pathBuf, pathLen);
        //}
        private PrincipalContext GetPrincipalContext()
        {
            if (oPrincipalContext == null)
            {
                oPrincipalContext = new PrincipalContext(ContextType.Machine, Environment.MachineName);
            }
            return oPrincipalContext;
        }
    }
}
