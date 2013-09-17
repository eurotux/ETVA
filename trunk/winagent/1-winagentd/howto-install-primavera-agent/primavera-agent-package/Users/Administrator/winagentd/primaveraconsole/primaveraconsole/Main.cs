using System;

using System.IO;

using System.Xml;

using System.Data;
using System.Data.Common;
using System.Data.SqlClient;

using System.Globalization;

using Interop.AdmBE800;
using Interop.AdmBS800;
using Interop.StdBE800;
using Interop.ErpBS800;

using Microsoft.SqlServer.Management.Smo;
using Microsoft.SqlServer.Management.Common;
using System.Collections.Generic;

namespace primaveraconsole
{
    class MainClass
	{
        private static Dictionary<string, string> parseArgs(string[] args)
        {
            Dictionary<string, string> options = new Dictionary<string, string>();
            foreach(string arg in args)
            {
                if( arg.StartsWith("/") )
                {
                    string[] vals = arg.Split('=');
                    string key = vals[0].Substring(1);
                    string value = vals[1];
                    //Common.WriteToConsoleOut(" key = " + key + " value " + value);

                    options.Add(key, value);
                }
            }
            return options;
        }

		private static Server serverInit( string u, string p, string i )
		{
			// server connection
			ServerConnection con = new ServerConnection();
			con.LoginSecure = false;
			con.Login = u;
			con.Password = p;
			con.ServerInstance = i;
			
			// use connection to connect Server
			Server srv = new Server(con);
			Common.WriteToConsoleOut("serverInit complete.");
			// initialization
			srv.Configuration.ShowAdvancedOptions.ConfigValue = 1;
			srv.Configuration.XPCmdShellEnabled.ConfigValue = 1;
			srv.Configuration.Alter();
			
			return srv;
		}
		private static void drop(Server srv, string d)
		{
			// Delete database before restoring it
			Database db = default(Database);
			db = srv.Databases[d];
			db.Drop();
			
			Common.WriteToConsoleOut("Drop complete.");
		}
		public static void drop(string u, string p, string i, string d)
		{
			// init server connection
			Server srv = serverInit(u,p,i);
					
			drop(srv,d);
		}		
		private static void restore(Server srv, string d, string f)
		{
			// Backup device
			BackupDeviceItem bdi = default(BackupDeviceItem);
			bdi = new BackupDeviceItem(f, DeviceType.File);
			
			// Delete database before restoring it
			Database db = default(Database);
			db = srv.Databases[d];
			
            //Define a Restore object variable.
            Restore rs = new Restore();

            //Set the NoRecovery property to true, so the transactions are not recovered. 
            rs.NoRecovery = false;
			
			// set to replace database
			rs.ReplaceDatabase = true;

            //Add the device that contains the full database backup to the Restore object. 
            rs.Devices.Add(bdi);

            //Specify the database name. 
            rs.Database = d;

            //Restore the full database backup with no recovery. 
            rs.SqlRestore(srv);
			
            //Inform the user that the Full Database Restore is complete. 
            Common.WriteToConsoleOut("Full Database Restore complete.");
		}
		public static void restore(string u, string p, string i, string d, string f = "")
		{
			// init server connection
			Server srv = serverInit(u,p,i);
			
			// use connection to connect Server
			Database db = default(Database);
			db = srv.Databases[d];

            if (f.Length == 0)
            {
                // get file from Last Backup date
                string bkdtString = db.LastBackupDate.ToString("yyyyMMddhhmmss");
                f = "Full_Backup_" + d + "_" + bkdtString;
            }

			Common.WriteToConsoleOut( "restore backup file: " + f );
				
			restore(srv,d,f);
		}		
		private static void backup(Server srv, string d)
		{
			Database db = default(Database);
			db = srv.Databases[d];
					
			Backup bk = new Backup();
			
			bk.Action = BackupActionType.Database;
			bk.BackupSetDescription = "Full backup for " + d;
			bk.BackupSetName = d + " backup";
			bk.Database = d;
			
			DateTime now = DateTime.Now;
			string nowString = now.ToString("yyyyMMddhhmmss");

			string f = "Full_Backup_" + d + "_" + nowString;
			BackupDeviceItem bdi = default(BackupDeviceItem);
			bdi = new BackupDeviceItem(f, DeviceType.File);
			
			bk.Devices.Add(bdi);
			bk.Incremental = false;
			
			System.DateTime backupdate = new System.DateTime();
			backupdate = new System.DateTime(2006,10,5);
			bk.ExpirationDate = backupdate;
			
			bk.LogTruncation = BackupTruncateLogType.Truncate;
			
			bk.SqlBackup(srv);
			
			Common.WriteToConsoleOut( "Full backup complete. Filename: " + f );
		}
		public static void backup(string u, string p, string i, string d)
		{
			// init server connection
			Server srv = serverInit(u,p,i);
			
			// do it
			backup(srv,d);
		}       
		public static void Main (string[] args)
		{

            Dictionary<string, string> options = parseArgs(args);
            try{
				if( args.Length>0 && args[0] == "backup" ){
                    string username, password, instance, database;
                    if (options.TryGetValue("username", out username) &&
                            options.TryGetValue("password", out password) && 
                            options.TryGetValue("instance", out instance) &&
                            options.TryGetValue("database", out database))
                    {
                        backup(username, password, instance, database);
                    } else {
                        Common.WriteToConsoleOut("Error: wrong number of parameters.");
                        Environment.Exit(-1);
                        return;
                    }
				} else if( args.Length>0 && args[0] == "restore" ){
					string username, password, instance, database, file;
                    if (options.TryGetValue("username", out username) &&
                            options.TryGetValue("password", out password) &&
                            options.TryGetValue("instance", out instance) &&
                            options.TryGetValue("database", out database) &&
                            options.TryGetValue("file", out file))
                    {
                        restore(username, password, instance, database, file);
                    } else if (options.TryGetValue("username", out username) &&
                            options.TryGetValue("password", out password) &&
                            options.TryGetValue("instance", out instance) &&
                            options.TryGetValue("database", out database))
                    {
                        restore(username, password, instance, database);
                    } else {
                        Common.WriteToConsoleOut("Error: wrong number of parameters.");
                        Environment.Exit(-1);
                        return;
                    }
				/*} else if( args.Length>0 && args[0] == "drop" ){
					drop(args[1],args[2],args[3],args[4]);*/
				} else if( args.Length>0 && args[0] == "serverinit" ){
					string username, password, instance;
                    if (options.TryGetValue("username", out username) &&
                            options.TryGetValue("password", out password) &&
                            options.TryGetValue("instance", out instance))
                    {
                        serverInit(username, password, instance);
                    } else {
                        Common.WriteToConsoleOut("Error: wrong number of parameters.");
                        Environment.Exit(-1);
                        return;
                    }
                } else if (args.Length > 0 && args[0].StartsWith("win")){
                    WindowsUsers oWindowsUsers = new WindowsUsers();

                    if (args.Length > 0 && args[0] == "windows_create_user")
                    {
                        string username, userpassword, groups;
                        if (options.TryGetValue("username", out username) &&
                                options.TryGetValue("userpassword", out userpassword))
                        {
                            if (!options.TryGetValue("groups", out groups)) groups = "";

                            if (!oWindowsUsers.create_user(username, userpassword, groups))
                            {
                                Environment.Exit(-1);
                                return;
                            }
                        }
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    } else if (args.Length > 0 && args[0] == "windows_get_user"){
                        string username;
                        if (options.TryGetValue("username", out username))
                        {
                            oWindowsUsers.get_user(username);
                        }
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "windows_list_users")
                    {
                        string groups;
                        if (!options.TryGetValue("groups", out groups)) groups = "";
                        oWindowsUsers.list_users(groups);
                    }
                } else if (args.Length > 0 && args[0].StartsWith("pri")){
                    string adminuser, adminpassword, instance, type;

                    if (!options.TryGetValue("adminuser", out adminuser) || !options.TryGetValue("adminpassword", out adminpassword))
                    {
                        Common.WriteToConsoleOut("Error: must specify username and password");
                        Environment.Exit(-1);
                        return;
                    }
                    /// Carregamento de Interops da pasta de ficheiros comuns.
                    AppDomain.CurrentDomain.AssemblyResolve += new ResolveEventHandler(CurrentDomain_AssemblyResolve);

                    Primavera primavera = new Primavera(adminuser, adminpassword);

                    if (options.TryGetValue("instance", out instance))
                        primavera.Instance = instance;
                    if (options.TryGetValue("type", out type))
                        primavera.Type = type;

                    if (args.Length > 0 && args[0] == "pricopiaseg")
                    {
                        string database;
                        if (options.TryGetValue("database", out database))
                            primavera.cria_copia_seguranca(database);
                    }
                    else if (args.Length > 0 && args[0] == "prirepocopiaseg")
                    {
                        string database, file;
                        if (options.TryGetValue("database", out database) && options.TryGetValue("file", out file))
                            primavera.reposicao_copia_seguranca(database, file);
                    }
                    else if (args.Length > 0 && args[0] == "prilistabkps")
                    {
                        primavera.lista_backups();
                    }
                    else if (args.Length > 0 && args[0] == "prilistaempresas")
                    {
                        primavera.lista_empresas();
                    }
                    else if (args.Length > 0 && args[0] == "prilistautilizadores")
                    {
                        primavera.lista_utilizadores();
                    }
                    else if (args.Length > 0 && args[0] == "prilistaperfis")
                    {
                        primavera.lista_perfis();
                    }
                    else if (args.Length > 0 && args[0] == "prilistabasesdados")
                    {
                        primavera.lista_basesdados();
                    }
                    else if (args.Length > 0 && args[0] == "priconfigbackups")
                    {
                        primavera.config_backups();
                    }
                    else if (args.Length > 0 && args[0] == "prilistaplanoscopiaseguranca")
                    {
                        primavera.lista_planos_copiaseguranca();
                    }
                    else if (args.Length > 0 && args[0] == "priinsereplanocopiaseguranca")
                    {
                        string name, verify, incremental, overwrite, companiesByComma, periodo;
                        if (options.TryGetValue("name", out name) && 
                                options.TryGetValue("verify", out verify) && 
                                options.TryGetValue("incremental", out incremental) && 
                                options.TryGetValue("overwrite", out overwrite) && 
                                options.TryGetValue("companiesByComma", out companiesByComma) && 
                                options.TryGetValue("periodo", out periodo) )
                        {
                            primavera.insere_plano_copiaseguranca(name, verify, incremental, overwrite, companiesByComma, periodo);
                        }
                        else
                        {
                            Common.WriteToConsoleOut("Error insere_plano_copiaseguranca: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priremoveplanocopiaseguranca")
                    {
                        string id;
                        if (options.TryGetValue("id", out id) )
                            primavera.remove_plano_copiaseguranca(id);
                    }
                    else if (args.Length > 0 && args[0] == "priinsereutilizador")
                    {
                        string codigo, nome, email, u_password, activo, administrador, perfil, 
                                naoPodeAlterarPwd, idioma, loginWindows, telemovel, bloqueado, 
                                tentativasFalhadas, autenticaoPersonalizada, superAdministrador, tecnico;
                        if (!options.TryGetValue("nome", out nome)) nome = "";
                        if (!options.TryGetValue("email", out email)) email = "";
                        if (!options.TryGetValue("userpassword", out u_password)) u_password = "";
                        if (!options.TryGetValue("activo", out activo)) activo = "1";
                        if (!options.TryGetValue("administrador", out administrador)) administrador = "0";
                        if (!options.TryGetValue("perfil", out perfil)) perfil = "";
                        if (!options.TryGetValue("naoPodeAlterarPwd", out naoPodeAlterarPwd)) naoPodeAlterarPwd = "0";
                        if (options.TryGetValue("idioma", out idioma))
                            idioma = CultureInfo.GetCultureInfoByIetfLanguageTag(idioma).LCID.ToString();
                        else idioma = "null";
                        if (!options.TryGetValue("loginWindows", out loginWindows)) loginWindows = "";
                        if (!options.TryGetValue("telemovel", out telemovel)) telemovel = "";
                        if (!options.TryGetValue("bloqueado", out bloqueado)) bloqueado = "0";
                        if (!options.TryGetValue("tentativasFalhadas", out tentativasFalhadas)) tentativasFalhadas = "0";
                        if (!options.TryGetValue("autenticaoPersonalizada", out autenticaoPersonalizada)) autenticaoPersonalizada = "0";
                        if (!options.TryGetValue("superAdministrador", out superAdministrador)) superAdministrador = "0";
                        if (!options.TryGetValue("tecnico", out tecnico)) tecnico = "0";

                        if (options.TryGetValue("codigo", out codigo))
                                primavera.insere_utilizador(codigo, nome, email, u_password, activo, administrador, perfil, naoPodeAlterarPwd, idioma, loginWindows, telemovel, bloqueado, tentativasFalhadas, autenticaoPersonalizada, superAdministrador, tecnico);
                        else
                        {
                            Common.WriteToConsoleOut("Error insere_utilizador: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priremoveutilizador")
                    {
                        string codigo;
                        if (options.TryGetValue("codigo", out codigo))
                            primavera.remove_utilizador(codigo);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priactualizautilizador")
                    {
                        string codigo, nome, email, u_password, activo, administrador, perfil,
                                naoPodeAlterarPwd, idioma, loginWindows, telemovel, bloqueado,
                                tentativasFalhadas, autenticaoPersonalizada, superAdministrador, tecnico;

                        if (!options.TryGetValue("nome", out nome)) nome = "";
                        if (!options.TryGetValue("email", out email)) email = "";
                        if (!options.TryGetValue("userpassword", out u_password)) u_password = "";
                        if (!options.TryGetValue("activo", out activo)) activo = "1";
                        if (!options.TryGetValue("administrador", out administrador)) administrador = "0";
                        if (!options.TryGetValue("perfil", out perfil)) perfil = "";
                        if (!options.TryGetValue("naoPodeAlterarPwd", out naoPodeAlterarPwd)) naoPodeAlterarPwd = "0";
                        if (options.TryGetValue("idioma", out idioma))
                            idioma = CultureInfo.GetCultureInfoByIetfLanguageTag(idioma).LCID.ToString();
                        else idioma = "null";
                        if (!options.TryGetValue("loginWindows", out loginWindows)) loginWindows = "";
                        if (!options.TryGetValue("telemovel", out telemovel)) telemovel = "";
                        if (!options.TryGetValue("bloqueado", out bloqueado)) bloqueado =  "0";
                        if (!options.TryGetValue("tentativasFalhadas", out tentativasFalhadas)) tentativasFalhadas = "0";
                        if (!options.TryGetValue("autenticaoPersonalizada", out autenticaoPersonalizada)) autenticaoPersonalizada = "0";
                        if (!options.TryGetValue("superAdministrador", out superAdministrador)) superAdministrador = "0";
                        if (!options.TryGetValue("tecnico", out tecnico)) tecnico = "0";

                        if (options.TryGetValue("codigo", out codigo))
                            primavera.actualiza_utilizador(codigo, nome, email, u_password, activo, administrador, perfil, naoPodeAlterarPwd, idioma, loginWindows, telemovel, bloqueado, tentativasFalhadas, autenticaoPersonalizada, superAdministrador, tecnico);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "prilistaaplicacoes")
                    {
                        primavera.lista_aplicacoes();
                    }
                    else if (args.Length > 0 && args[0] == "prilista_utilizador_aplicacoes")
                    {
                        string user;
                        if (options.TryGetValue("user", out user))
                            primavera.lista_utilizador_aplicacoes(user);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priinsere_utilizador_aplicacoes")
                    {
                        string user, apl;
                        if (options.TryGetValue("user", out user) &&
                                options.TryGetValue("apl", out apl))
                            primavera.insere_utilizador_aplicacao(user,apl);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priremove_utilizador_aplicacoes")
                    {
                        string user, apl;
                        if (options.TryGetValue("user", out user) &&
                                options.TryGetValue("apl", out apl))
                            primavera.remove_utilizador_aplicacao(user, apl);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "prilista_utilizador_permissoes")
                    {
                        string user;
                        if (options.TryGetValue("user", out user))
                            primavera.lista_utilizador_permissoes(user);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priactualiza_utilizador_aplicacoes")
                    {
                        string user, s_aplicacoes;

                        if (options.TryGetValue("user", out user) &&
                                options.TryGetValue("aplicacoes", out s_aplicacoes))
                        {
                            string[] aplicacoes = s_aplicacoes.Split(',');
                            
                            if (!primavera.actualiza_utilizador_aplicacoes(user, aplicacoes))
                            {
                                Environment.Exit(-1);
                            }
                        }
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priinsere_utilizador_permissoes")
                    {
                        string user, perfil, empresa;
                        if (options.TryGetValue("user", out user) &&
                                options.TryGetValue("perfil", out perfil) &&
                                options.TryGetValue("empresa", out empresa))
                            primavera.insere_utilizador_permissao(user, perfil, empresa);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priremove_utilizador_permissoes")
                    {
                        string user, perfil, empresa;
                        if (options.TryGetValue("user", out user) &&
                                options.TryGetValue("perfil", out perfil) &&
                                options.TryGetValue("empresa", out empresa))
                            primavera.remove_utilizador_permissao(user, perfil, empresa);
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }
                    else if (args.Length > 0 && args[0] == "priactualiza_utilizador_permissoes")
                    {
                        string user, s_permissoes;

                        if (options.TryGetValue("user", out user) &&
                                options.TryGetValue("permissoes", out s_permissoes))
                        {
                            string[] l_permissoes = s_permissoes.Split(';');
                            List<string[]> list_permissoes = new List<string[]>();
                            foreach (string s_permissao in l_permissoes)
                            {
                                list_permissoes.Add(s_permissao.Split(','));                                
                            }
                            string[][] permissoes = list_permissoes.ToArray();
                            if (!primavera.actualiza_utilizador_permissoes(user, permissoes))
                            {
                                Environment.Exit(-1);
                            }
                        }
                        else
                        {
                            Common.WriteToConsoleOut("Error: wrong number of parameters.");
                            Environment.Exit(-1);
                            return;
                        }
                    }

                    primavera.end();
				} else {
                    string adminuser, adminpassword, instance, type;

                    if (!options.TryGetValue("adminuser", out adminuser) || !options.TryGetValue("adminpassword", out adminpassword))
                    {
                        Common.WriteToConsoleOut("Error: must specify username and password");
                        Environment.Exit(-1);
                        return;
                    }

                    Primavera primavera = new Primavera(adminuser,adminpassword);

                    if (options.TryGetValue("instance", out instance))
                        primavera.Instance = instance;
                    if (options.TryGetValue("type", out type))
                        primavera.Type = type;
                    
                    primavera.info();
                    primavera.end();
				}
			}
			catch( Exception e ){
				Common.WriteToConsoleOut("{0} Exception caught.", e);
				Environment.Exit(-1);
			}
		}

        /// <summary>
        /// Método para resolução das assemblies.
        /// </summary>
        /// <param name="sender">Application</param>
        /// <param name="args">Resolving Assembly Name</param>
        /// <returns>Assembly</returns>
        static System.Reflection.Assembly CurrentDomain_AssemblyResolve(object sender, ResolveEventArgs args)
        {
            string assemblyFullName;
            System.Reflection.AssemblyName assemblyName;
            const string PRIMAVERA_COMMON_FILES_FOLDER = "PRIMAVERA\\SG800"; //pasta dos ficheiros comuns especifica da versão do ERP PRIMAVERA utilizada.
            assemblyName = new System.Reflection.AssemblyName(args.Name);
            assemblyFullName = System.IO.Path.Combine(System.IO.Path.Combine(System.Environment.GetFolderPath(Environment.SpecialFolder.CommonProgramFilesX86), PRIMAVERA_COMMON_FILES_FOLDER), assemblyName.Name + ".dll");
            if (System.IO.File.Exists(assemblyFullName))
                return System.Reflection.Assembly.LoadFile(assemblyFullName);
            else
                return null;
        }
	}
}

