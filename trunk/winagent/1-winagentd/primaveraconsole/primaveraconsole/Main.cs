using System;

using System.IO;

using System.Xml;

using System.Data;
using System.Data.Common;
using System.Data.SqlClient;

using Interop.AdmBE750;
using Interop.AdmBS750;
using Interop.StdBE750;
using Interop.ErpBS750;

using Microsoft.SqlServer.Management.Smo;
using Microsoft.SqlServer.Management.Common;

namespace primaveraconsole
{
	class MainClass
	{
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
			Console.WriteLine("serverInit complete.");
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
			
			Console.WriteLine("Drop complete.");
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
            Console.WriteLine("Full Database Restore complete.");
		}
		public static void restore(string u, string p, string i, string d)
		{
			// init server connection
			Server srv = serverInit(u,p,i);
			
			// use connection to connect Server
			Database db = default(Database);
			db = srv.Databases[d];
			
			// get file from Last Backup date
			string bkdtString = db.LastBackupDate.ToString("yyyyMMddhhmmss");
			string f = "Full_Backup_" + d + "_" + bkdtString;
			Console.WriteLine( "restore backup file: " + f );
				
			restore(srv,d,f);
		}
		public static void restore(string u, string p, string i, string d, string f)
		{
			// init server connection
			Server srv = serverInit(u,p,i);
			
			Console.WriteLine( "restore backup file: " + f );
			
			restore(srv,d,f);
		}
		
		private static void backup(Server srv, string d)
		{			Database db = default(Database);
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
			
			Console.WriteLine( "Full backup complete. Filename: " + f );
		}
		public static void backup(string u, string p, string i, string d)
		{
			// init server connection
			Server srv = serverInit(u,p,i);
			
			// do it
			backup(srv,d);
		}
		public static void callprimavera_cria_copia_seguranca(string u, string p, string i, string d)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;

			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			string bkpname = d + " backup";
			string bkpdescription = "Full backup for " + d;
			string dir = "";

			DateTime now = DateTime.Now;
			string nowString = now.ToString("yyyyMMddhhmmss");
			
			string f = "Full_Backup_" + d + "_" + nowString;
			Console.WriteLine( "Backup file: " + f );
			
			adm.BasesDados.CopiaSeguranca( ref d, ref bkpname, ref bkpdescription, ref dir, ref f);
			
			adm.FechaPRIEMPRE();
			return;
		}
		public static void callprimavera_cria_copia_seguranca(string u, string p, string d)
		{
			callprimavera_cria_copia_seguranca(u, p, "DEFAULT", d);
		}
		public static void callprimavera_reposicao_copia_seguranca(string u, string p, string i, string b, string f)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();

			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;

			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			Console.WriteLine( "Reposicao de copia seguranca na base dados: " + b + "; com ficheiro: " + f );
			
			adm.BasesDados.ReposicaoCompletaCopiaSeguranca( ref b, ref f );
			//string sqlRestore = "RESTORE DATABASE " + b + " FROM DISK = '" + f + "' WITH RECOVERY ";
			//adm.SQLServer.ExecutaComando(sqlRestore, "master", false);

			adm.FechaPRIEMPRE();			
			return;
		}
		public static void callprimavera_reposicao_copia_seguranca(string u, string p, string b, string f)
		{
			callprimavera_reposicao_copia_seguranca(u, p, "DEFAULT", b, f);
		}
		public static void read_backupfile( FileInfo fi )
		{
			//Server srv = new Server();
			Server srv = serverInit("sa","sa123",".\\PRIMAVERA");
			Restore res = new Restore();
			
			res.Devices.AddDevice(fi.FullName, DeviceType.File );
			DataTable dt = res.ReadBackupHeader(srv);
			
			foreach( DataRow r in dt.Rows )
			{
				foreach( DataColumn c in dt.Columns )
				{
					Console.WriteLine( c.ToString() + " = " + r[c].ToString() );
				}
			}
		}
		public static void callprimavera_lista_backups(string u, string p, string i)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			string bkpdir = adm.SQLServer.DirectoriaBackup();
			
			//Console.WriteLine( "bkpdir: " + bkpdir );
			
			DirectoryInfo dirInfo = new DirectoryInfo(bkpdir);
			FileInfo[] filenames = dirInfo.GetFiles("*.*");
			
			// sort file names
			Array.Sort(filenames, (a, b) => DateTime.Compare(b.LastWriteTime, a.LastWriteTime));
			foreach( FileInfo fi in filenames )
			{
				Console.WriteLine("{0};{1};{2};{3};{4}", fi.Name, fi.CreationTime, fi.LastWriteTime, fi.Length, fi.FullName);
				// TODO read backup file for get more info
				//read_backupfile(fi);
			}
			
			adm.FechaPRIEMPRE();
			return;
		}
		public static void callprimavera_lista_backups(string u, string p)
		{
			callprimavera_lista_backups(u, p, "DEFAULT");
		}
		public static void callprimavera_lista_basesdados(string u, string p, string i)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			AdmBEBasesDados abds = adm.BasesDados.ListaBasesDados();
            foreach( AdmBEBaseDados bd in abds ){
                Console.WriteLine( "name: " +  bd.get_Nome() );
            }

			adm.FechaPRIEMPRE();
			return;
		}
		public static void callprimavera_lista_basesdados(string u, string p)
		{
			callprimavera_lista_basesdados(u,p,"DEFAULT");
		}
		public static void callprimavera_config_backups(string u, string p, string i)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			string bkpdir = adm.SQLServer.DirectoriaBackup();
			
			Console.WriteLine( "DirectoriaBackup: " + bkpdir );
			
			adm.FechaPRIEMPRE();
			return;
		}
		public static void callprimavera_config_backups(string u, string p)
		{
			callprimavera_config_backups(u,p,"DEFAULT");
		}
		public static void callprimavera_lista_planos_copiaseguranca(string u, string p, string i)
        {
            AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);

            AdmBEPlanosCopiasSeg lista = adm.PlanosCopiasSeguranca.ListaPlanos();
            foreach (AdmBEPlanoCopiasSeg pl in lista)
            {
                string id = pl.get_Id();
                Console.WriteLine("PlanoCopiasSeg_id: " + id);

                string xmlPlano = pl.get_Plano();
                //Console.WriteLine(" xml: " + xmlPlano);

                XmlReader xmlreader = XmlReader.Create(new StringReader(xmlPlano));

                //xmlreader.Read();
                xmlreader.ReadToFollowing("backupPlan");
                Console.WriteLine(" id: " + xmlreader.GetAttribute("id"));
                Console.WriteLine(" name: " + xmlreader.GetAttribute("name"));
                Console.WriteLine(" verify: " + xmlreader.GetAttribute("verify"));
                Console.WriteLine(" incremental: " + xmlreader.GetAttribute("incremental"));
                Console.WriteLine(" overwrite: " + xmlreader.GetAttribute("overwrite"));
                Console.WriteLine(" destination: " + xmlreader.GetAttribute("destination"));
                //Console.WriteLine(" schedule: " + xmlreader.GetAttribute("schedule"));
                Console.WriteLine(" date: " + xmlreader.GetAttribute("date"));
                Console.WriteLine(" lastExecution: " + xmlreader.GetAttribute("lastExecution"));
                Console.WriteLine(" nextExecution: " + xmlreader.GetAttribute("nextExecution"));

                string schedule_id = xmlreader.GetAttribute("schedule");
                Console.WriteLine(" schedule id: " + schedule_id);
                AdmBECalendario pcal = adm.Calendario.Edita(schedule_id);
                //Console.WriteLine(" schedule_id: " + pcal.Id );
                Console.WriteLine("  schedule_periodo: " + pcal.get_Periodo().ToString());

                xmlreader.ReadToFollowing("companies");
                while (xmlreader.ReadToFollowing("company"))
                {
                    xmlreader.ReadToFollowing("properties");
                    Console.WriteLine(" company_key: " + xmlreader.GetAttribute("key"));
                    Console.WriteLine(" company_name: " + xmlreader.GetAttribute("name"));
                }

                //xmlreader.ReadEndElement();
            }

            adm.FechaPRIEMPRE();
        }
        public static void callprimavera_insere_plano_copiaseguranca(string u, string p, string i, string name, string verify, string incremental, string overwrite, string companiesByComma, string periodo)
        {
            AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);

            string newid = System.Guid.NewGuid().ToString();

            AdmBEPlanoCopiasSeg newPC = new AdmBEPlanoCopiasSegClass();
            AdmBECalendario objCal = new AdmBECalendarioClass();
            
            newPC.set_Id(newid);
            objCal.Id = newid;

            if (periodo.Equals("mensal"))
                objCal.set_Periodo(EnumPeriodoExecucao.prMensal);
            else if (periodo.Equals("semanal"))
                objCal.set_Periodo(EnumPeriodoExecucao.prSemanal);
            else
                objCal.set_Periodo(EnumPeriodoExecucao.prDiario);

            // Exec 23h (TODO change this by arg)
            objCal.set_FreqUnicaHora(new DateTime(1900, 1, 1, 23, 0, 0));

            adm.Calendario.Actualiza(objCal);
            
            StringWriter stringwriter = new StringWriter();

            XmlWriterSettings xmlsettings = new XmlWriterSettings();
            xmlsettings.OmitXmlDeclaration = true;
            xmlsettings.Indent = false;
            XmlWriter xmlwriter = XmlWriter.Create(stringwriter, xmlsettings);

            xmlwriter.WriteStartElement("backupPlan");
            xmlwriter.WriteAttributeString("id", "{" + newPC.get_Id() + "}");

            xmlwriter.WriteAttributeString("name", name);
            xmlwriter.WriteAttributeString("verify", verify);
            xmlwriter.WriteAttributeString("incremental", incremental);
            xmlwriter.WriteAttributeString("overwrite", overwrite);
            
            xmlwriter.WriteAttributeString("destination", adm.SQLServer.DirectoriaBackup());
            xmlwriter.WriteAttributeString("schedule", "{" + objCal.Id + "}");

            //Console.WriteLine(" date: " + xmlreader.GetAttribute("date"));
            DateTime datenow = DateTime.Now;
            xmlwriter.WriteAttributeString("date", datenow.ToString("dd-MM-yyyy HH:mm:ss"));
            
            //Console.WriteLine(" lastExecution: " + xmlreader.GetAttribute("lastExecution"));
            DateTime lastdate = objCal.UltimaOcorrencia;
            xmlwriter.WriteAttributeString("lastExecution", lastdate.ToString("dd-MM-yyyy HH:mm:ss"));

            //Console.WriteLine(" nextExecution: " + xmlreader.GetAttribute("nextExecution"));
            //DateTime nextdate = new DateTime(datenow.Year,datenow.Month,datenow.Day);
            DateTime nextdate = objCal.ProximaOcorrencia;
            xmlwriter.WriteAttributeString("nextExecution", nextdate.ToString("dd-MM-yyyy HH:mm:ss"));

            // companies
            xmlwriter.WriteStartElement("companies");

            //string companiesByComma = "DEMO,PRIDEMO;DEMOX,PRIDEMOX";
            string[] companies = companiesByComma.Split(new char[] { ';' });

            foreach (string company in companies)
            {
                string[] cfields = company.Split(new char[] { ',' });
                if (cfields.Length == 2)
                {
                    xmlwriter.WriteStartElement("company");

                    xmlwriter.WriteStartElement("properties");
                    xmlwriter.WriteAttributeString("key", cfields[0]);
                    xmlwriter.WriteAttributeString("name", cfields[1]);
                    xmlwriter.WriteEndElement(); // properties

                    xmlwriter.WriteEndElement(); // company
                }
            }

            xmlwriter.WriteEndElement(); // companies

            xmlwriter.WriteEndElement(); // backupPlan

            xmlwriter.Flush();

            //Console.WriteLine("xml string: " + stringwriter.ToString());

            //string strBackupPlan = "<backupPlan id=\"" + newpc_id + "\" name=\"teste all\" verify=\"False\" incremental=\"False\" overwrite=\"False\" destination=\"C:\\PROGRAM FILES\\MICROSOFT SQL SERVER\\MSSQL10.PRIMAVERA\\MSSQL\\BACKUP\\\" schedule=\"" + newpc_id + "\" date=\"" + DateTime.Now.ToString() + "\" lastExecution=\"undefined\" nextExecution=\"" + DateTime.Now.ToString("dd-MM-yyyy") + " 23:00:00\"><companies><company><properties key=\"OBIADM\" name=\"BIADM\"/></company><company><properties key=\"EDEMO\" name=\"PRIDEMO\"/></company><company><properties key=\"EDEMOX\" name=\"PRIDEMOX\"/></company><company><properties key=\"OPRIEMPRE\" name=\"PRIEMPRE\"/></company></companies></backupPlan>";
            newPC.set_Plano(stringwriter.ToString());

            adm.PlanosCopiasSeguranca.Actualiza(newPC);
            adm.PlanosCopiasSeguranca.ListaPlanos().Insere(newPC);

            Console.WriteLine(" Plano de Copia Seguranca inserido com id: " + newPC.get_Id());

            adm.FechaPRIEMPRE();
        }
		public static void callprimavera_remove_plano_copiaseguranca(string u, string p, string i, string id)
        {
            AdmBS adm = new AdmBSClass();

            StdBETransaccao objtrans = new StdBETransaccaoClass();

            EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;

            adm.AbrePRIEMPRE(ref t, ref u, ref p, ref objtrans, ref i);
            /*AdmBE750.AdmBEPlanoCopiasSeg pl = adm.PlanosCopiasSeguranca.Edita(id);
            adm.PlanosCopiasSeguranca.ListaPlanos().Remove(pl);*/
			adm.Calendario.Remove(id);
            adm.PlanosCopiasSeguranca.Remove(id);

            Console.WriteLine(" Plano de Copia Seguranca com id: " + id + " removido.");

            adm.FechaPRIEMPRE();
        }
		public static void callprimavera_lista_empresas(string u, string p, string i)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			AdmBEEmpresas empresas = adm.Empresas.ListaEmpresas(true);
            foreach(AdmBEEmpresa e in empresas)
            {
                Console.WriteLine("name: " + e.get_Identificador() + " description: " + e.get_IDNome() );
            }

			adm.FechaPRIEMPRE();
			return;
		}
		public static void callprimavera_lista_empresas(string u, string p)
		{
			callprimavera_lista_empresas(u, p, "DEFAULT");
		}
		public static void callprimavera_lista_utilizadores(string u, string p, string i)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			StdBELista uList = adm.Consulta( "SELECT * FROM utilizadores" );
			
			uList.Inicio();
			while( !uList.NoFim() ){
				
				Console.WriteLine( "Utilizador: " + uList.Valor("Codigo"));
				Console.WriteLine( " Codigo: " + uList.Valor("Codigo"));
				Console.WriteLine( " Nome: " + uList.Valor("Nome"));
				Console.WriteLine( " Email: " + uList.Valor("Email"));
				Console.WriteLine( " Activo: " + uList.Valor("Activo"));
				Console.WriteLine( " Administrador: " + uList.Valor("Administrador"));
				Console.WriteLine( " PerfilSugerido: " + uList.Valor("PerfilSugerido"));
				Console.WriteLine( " NaoPodeAlterarPwd: " + uList.Valor("NaoPodeAlterarPwd"));
				Console.WriteLine( " Idioma: " + uList.Valor("Idioma"));
				Console.WriteLine( " LoginWindows: " + uList.Valor("LoginWindows"));
				Console.WriteLine( " Telemovel: " + uList.Valor("Telemovel"));
				Console.WriteLine( " Bloqueado: " + uList.Valor("Bloqueado"));
				Console.WriteLine( " TentativasFalhadas: " + uList.Valor("TentativasFalhadas"));
				Console.WriteLine( " AutenticacaoPersonalizada: " + uList.Valor("AutenticacaoPersonalizada"));
				Console.WriteLine( " SuperAdministrador: " + uList.Valor("SuperAdministrador"));
				Console.WriteLine( " Tecnico: " + uList.Valor("Tecnico"));
				
				uList.Seguinte();
			}

			adm.FechaPRIEMPRE();
			return;
		}
		public static void callprimavera_lista_utilizadores(string u, string p)
		{
			callprimavera_lista_utilizadores(u, p, "DEFAULT");
		}
		
		public static void callprimavera_insere_utilizador(string u, string p, string i, string codigo, string nome, string email, string password, string activo, string administrador, string perfilSugerido, string naoPodeAlterarPwd, string idioma, string loginWindows, string telemovel, string bloqueado, string tentativasFalhadas, string autenticacaoPersonalizada, string superAdministrador, string tecnico)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
            string sqlInsereUtilizador = "INSERT [Utilizadores] ([Codigo], [Nome], [Email], [Password], [Activo], [Administrador], [PerfilSugerido], [NaoPodeAlterarPwd], [Idioma], [LoginWindows], [Telemovel], [Bloqueado], [TentativasFalhadas], [AutenticacaoPersonalizada], [SuperAdministrador], [Tecnico]) VALUES (N'"+ codigo + "',N'" + nome + "',N'" + email + "',N'" + password + "'," + activo + "," + administrador + ",N'" + perfilSugerido + "'," + naoPodeAlterarPwd + "," + idioma + ",N'" + loginWindows + "',N'" + telemovel + "'," + bloqueado + "," + tentativasFalhadas + "," + autenticacaoPersonalizada + "," + superAdministrador + "," + tecnico + ")";
			adm.SQLServer.ExecutaComando(sqlInsereUtilizador, "PRIEMPRE", false);
			Console.WriteLine("Insert user '" + codigo + "' ok." );
			
			adm.FechaPRIEMPRE();
		}
		public static void callprimavera_actualiza_utilizador(string u, string p, string i, string codigo, string nome, string email, string password, string activo, string administrador, string perfilSugerido, string naoPodeAlterarPwd, string idioma, string loginWindows, string telemovel, string bloqueado, string tentativasFalhadas, string autenticacaoPersonalizada, string superAdministrador, string tecnico)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
            string sqlActualizaUtilizador = "UPDATE [Utilizadores] SET [Nome] = '" + nome + "', [Email] = '" + email + "', [Activo] = " + activo + ", [Administrador] = " + administrador + ", [PerfilSugerido] = '" + perfilSugerido + "', [NaoPodeAlterarPwd] = " + naoPodeAlterarPwd + ", [Idioma] = " + idioma + ", [LoginWindows] = '" + loginWindows + "', [Telemovel] = '" + telemovel + "', [Bloqueado] = " + bloqueado + ", [TentativasFalhadas] = " + tentativasFalhadas + ", [AutenticacaoPersonalizada] = " + autenticacaoPersonalizada + ", [SuperAdministrador] = " + superAdministrador + ", [Tecnico] = " + tecnico;
			if( password.Length > 0 ){
				sqlActualizaUtilizador = sqlActualizaUtilizador + ", [Password] = '" + password + "'";
			}
			sqlActualizaUtilizador = sqlActualizaUtilizador + " WHERE [Codigo] = '" + codigo + "'";
			
			//Console.WriteLine(sqlActualizaUtilizador);
			
			adm.SQLServer.ExecutaComando(sqlActualizaUtilizador, "PRIEMPRE", true);
			Console.WriteLine("Update user '" + codigo + "' ok." );
			
			adm.FechaPRIEMPRE();
		}
		public static void callprimavera_remove_utilizador(string u, string p, string i, string codigo )
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
            string sqlRemoveUtilizador = "DELETE [Utilizadores] WHERE [Codigo] = '"+ codigo + "'";
			
			//Console.WriteLine(sqlRemoveUtilizador);
			
			adm.SQLServer.ExecutaComando(sqlRemoveUtilizador, "PRIEMPRE", true);
			Console.WriteLine("Delete user '" + codigo + "' ok." );
			
			adm.FechaPRIEMPRE();
		}
		public static void callprimavera_testerestore(string u, string p, string i)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			
			//EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;
			//adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
            string sqlTesteRestore = "RESTORE DATABASE PRIEMPRE FROM DISK = 'C:\\Program Files\\Microsoft SQL Server\\MSSQL10.PRIMAVERA\\MSSQL\\Backup\\Full_Backup_PRIEMPRE_20110530013832' WITH RECOVERY ";
			
			Console.WriteLine(sqlTesteRestore);
			adm.SQLServer.ExecutaComando(sqlTesteRestore, "master", false);
			Console.WriteLine("Teste Restore ok." );
			
			//adm.FechaPRIEMPRE();
		}

		public static void callprimavera(string u, string p, string i)
		{
			AdmBS adm = new AdmBSClass();
			StdBETransaccao objtrans = new StdBETransaccaoClass();
			ErpBS motor = new ErpBSClass();
			
			EnumTipoPlataforma t = EnumTipoPlataforma.tpProfissional;

			adm.AbrePRIEMPRE( ref t,  ref u, ref p, ref objtrans,  ref i);
			
			bool _false = false;
			
            Console.WriteLine( "License: " + !motor.Licenca.VersaoDemo );
			Console.WriteLine( "Language: " + adm.Params.get_Idioma() );
			Console.WriteLine( "Seguranca Activa: " + adm.Params.get_SegurancaActiva() );
			Console.WriteLine( "Seguranca Pro Emp Activa: " + adm.Params.get_SegurancaPorEmpActiva() );
			Console.WriteLine( "Modo Seguranca: " + adm.Params.get_SegurancaActiva() );
			Console.WriteLine( "N Postos: " + adm.Postos.ListaPostos(ref _false).NumItens );
			Console.WriteLine( "DirectoriaBackup: " + adm.SQLServer.DirectoriaBackup() );
			
			StdBELista uList = adm.Consulta( "SELECT * FROM utilizadores" );
			Console.WriteLine( "N Utilizadores: " + uList.NumLinhas() );
			
			uList.Inicio();
			while( !uList.NoFim() ){
				Console.WriteLine( " Utilizador: " + uList.Valor("Codigo") + ", " + uList.Valor("Nome") );
				uList.Seguinte();
			}
			
			StdBELista eList = adm.Consulta( "SELECT * FROM empresas" );
			Console.WriteLine( "N Empresas: " + eList.NumLinhas() );
			
			eList.Inicio();
			while( !eList.NoFim() ){
				Console.WriteLine( " Empresa: " + eList.Valor("Codigo") + ", " + eList.Valor("IDNome") );
				eList.Seguinte();
			}
			
			adm.FechaPRIEMPRE();
			
			return;
		}
		public static void callprimavera(string u, string p)
		{
			callprimavera(u, p, "DEFAULT");
		}

		
		public static void Main (string[] args)
		{
			try{
				if( args.Length>0 && args[0] == "backup" ){
					backup(args[1],args[2],args[3],args[4]);
				} else if( args.Length>0 && args[0] == "restore" ){
					if( args.Length>5 )
						 restore(args[1],args[2],args[3],args[4],args[5]);
					else restore(args[1],args[2],args[3],args[4]);
				/*} else if( args.Length>0 && args[0] == "drop" ){
					drop(args[1],args[2],args[3],args[4]);*/
				} else if( args.Length>0 && args[0] == "serverinit" ){
					serverInit(args[1],args[2],args[3]);
				} else if( args.Length>0 && args[0] == "pricopiaseg" ){
					if( args.Length > 4 )
				    	 callprimavera_cria_copia_seguranca(args[1],args[2],args[3],args[4]);
					else callprimavera_cria_copia_seguranca(args[1],args[2],args[3]);
				} else if( args.Length>0 && args[0] == "prirepocopiaseg" ){
					if( args.Length > 5 )
				    	 callprimavera_reposicao_copia_seguranca(args[1],args[2],args[3],args[4],args[5]);
					else callprimavera_reposicao_copia_seguranca(args[1],args[2],args[3],args[4]);
				} else if( args.Length>0 && args[0] == "prilistabkps" ){
					if( args.Length > 3 )
				    	 callprimavera_lista_backups(args[1],args[2],args[3]);
					else callprimavera_lista_backups(args[1],args[2]);
				} else if( args.Length>0 && args[0] == "prilistaempresas" ){
					if( args.Length > 3 )
				    	 callprimavera_lista_empresas(args[1],args[2],args[3]);
					else callprimavera_lista_empresas(args[1],args[2]);
				} else if( args.Length>0 && args[0] == "prilistautilizadores" ){
					if( args.Length > 3 )
				    	 callprimavera_lista_utilizadores(args[1],args[2],args[3]);
					else callprimavera_lista_utilizadores(args[1],args[2]);
				} else if( args.Length>0 && args[0] == "prilistabasesdados" ){
					if( args.Length > 3 )
				    	 callprimavera_lista_basesdados(args[1],args[2],args[3]);
					else callprimavera_lista_basesdados(args[1],args[2]);
				} else if( args.Length>0 && args[0] == "priconfigbackups" ){
					if( args.Length > 3 )
				    	 callprimavera_config_backups(args[1],args[2],args[3]);
					else callprimavera_config_backups(args[1],args[2]);
				} else if( args.Length>0 && args[0] == "prilistaplanoscopiaseguranca" ){
					if( args.Length > 3 )
						 callprimavera_lista_planos_copiaseguranca(args[1],args[2],args[3]);
					else callprimavera_lista_planos_copiaseguranca(args[1],args[2],"DEFAULT");
				} else if( args.Length>0 && args[0] == "priinsereplanocopiaseguranca" ){
					if( args.Length > 9 )
						callprimavera_insere_plano_copiaseguranca(args[1],args[2],args[3],args[4],args[5],args[6],args[7],args[8],args[9]);
					else if( args.Length > 8 )
						callprimavera_insere_plano_copiaseguranca(args[1],args[2],"DEFAULT",args[3],args[4],args[5],args[6],args[7],args[8]);
					else {
						Console.WriteLine( "Error callprimavera_insere_plano_copiaseguranca: wrong number of parameters." );
						Environment.Exit( -1 );
						return;
					}
				} else if( args.Length>0 && args[0] == "priremoveplanocopiaseguranca" ){
					if( args.Length > 4 )
						callprimavera_remove_plano_copiaseguranca(args[1],args[2],args[3],args[4]);
					else if( args.Length > 3 )
						callprimavera_remove_plano_copiaseguranca(args[1],args[2],"DEFAULT",args[3]);
				} else if( args.Length>0 && args[0] == "priinsereutilizador" ){
					//string u, string p, string i, string codigo, string nome, string email, string password, string activo, string administrador, string perfilSugerido, string naoPodeAlterarPwd, string idioma, string loginWindows, string telemovel, string bloqueado, string tentativasFalhadas, string autenticacaoPersonalizada, string superAdministrador, string tecnico
					//callprimavera_insere_utilizador(string u, string p, string i, string codigo, string nome, string email, string password, string activo, string administrador, string perfilSugerido, string naoPodeAlterarPwd, string idioma, string loginWindows, string telemovel, string bloqueado, string tentativasFalhadas, string autenticacaoPersonalizada, string superAdministrador, string tecnico)
					if( args.Length == 20 )
						callprimavera_insere_utilizador(args[1], args[2], args[3], args[4], args[5], args[6], args[7], args[8], args[9], args[10], args[11], args[12], args[13], args[14], args[15], args[16], args[17], args[18], args[19]);
					else if( args.Length == 10 )
						callprimavera_insere_utilizador(args[1], args[2], "DEFAULT", args[3], args[4], args[5], args[6], "1", args[7], "", "0", "NULL", "", "", "0", "0", "0", args[8], args[9]);
					else {
						Console.WriteLine( "Error: must specify username and password" );
						Environment.Exit( -1 );
						return;
					}
				} else if( args.Length>0 && args[0] == "priremoveutilizador" ){
					if( args.Length == 5 )
						callprimavera_remove_utilizador(args[1], args[2], args[3], args[4] );
					else if( args.Length == 4 )
						callprimavera_remove_utilizador(args[1], args[2], "DEFAULT", args[3] );
					else {
						Console.WriteLine( "Error: must specify username and password" );
						Environment.Exit( -1 );
						return;
					}
				} else if( args.Length>0 && args[0] == "priactualizautilizador" ){
					if( args.Length == 20 )
						callprimavera_actualiza_utilizador(args[1], args[2], args[3], args[4], args[5], args[6], args[7], args[8], args[9], args[10], args[11], args[12], args[13], args[14], args[15], args[16], args[17], args[18], args[19]);
					else if( args.Length == 10 )
						callprimavera_actualiza_utilizador(args[1], args[2], "DEFAULT", args[3], args[4], args[5], args[6], "1", args[7], "", "0", "NULL", "", "", "0", "0", "0", args[8], args[9]);
					else {
						Console.WriteLine( "Error: must specify username and password" );
						Environment.Exit( -1 );
						return;
					}
					//callprimavera_testerestore
				} else if( args.Length>0 && args[0] == "pritesterestore" ){
					callprimavera_testerestore(args[1], args[2], "DEFAULT");
				} else {
					if( args.Length < 2 ){
						Console.WriteLine( "Error: must specify username and password" );
						Environment.Exit( -1 );
						return;
					}
		            string u = args[0]; // "adm";
		            string p = args[1]; // "123";
					if( args.Length > 2 )
						 callprimavera(u,p,args[2]);
					else callprimavera(u,p);					
				}
			}
			catch( Exception e ){
				Console.WriteLine("{0} Exception caught.", e);
				Environment.Exit(-1);
			}
		}
	}
}

