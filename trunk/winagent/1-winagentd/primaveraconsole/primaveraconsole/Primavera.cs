using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

using Interop.AdmBE800;
using Interop.AdmBS800;
using Interop.StdBE800;
using Interop.ErpBS800;

using System.IO;
using System.Xml;

using Microsoft.Win32;
using System.Globalization;

namespace primaveraconsole
{
    class Primavera
    {
        public string User { get; set; }
        public string Password { get; set; }
        public string Instance { get; set; }
        public string Type { get; set; }

        private AdmBS adm { get; set; }

        public Primavera(string user, string password, string instance = "DEFAULT", string type = "Executive")
        {
            User = user;
            Password = password;
            Instance = instance;
            Type = type;
        }
        public void init()
        {
            if (adm == null)
            {
                adm = new AdmBS();
                StdBETransaccao objtrans = new StdBETransaccao();

                EnumTipoPlataforma tp = EnumTipoPlataforma.tpProfissional;
                if (Type.Equals("Executive"))
                    tp = EnumTipoPlataforma.tpEmpresarial;

                adm.AbrePRIEMPRE(ref tp, User, Password, ref objtrans, Instance);
            }
        }
        public void end()
        {
            adm.FechaPRIEMPRE();
        }

        public void cria_copia_seguranca(string database)
        {
            init();

            string bkpname = database + " backup";
            string bkpdescription = "Full backup for " + database;
            string dir = "";

            DateTime now = DateTime.Now;
            string nowString = now.ToString("yyyyMMddhhmmss");

            string f = "Full_Backup_" + database + "_" + nowString;
            Common.WriteToConsoleOut("Backup file: " + f);

            adm.BasesDados.CopiaSeguranca(ref database, ref bkpname, ref bkpdescription, ref dir, ref f);
            return;
        }
        public void reposicao_copia_seguranca(string database, string file)
        {
            init();
            Common.WriteToConsoleOut("Reposicao de copia seguranca na base dados: " + database + "; com ficheiro: " + file);

            adm.BasesDados.ReposicaoCompletaCopiaSeguranca(ref database, ref file);
            return;
        }
        public void lista_backups()
        {
            init();
            string bkpdir = adm.SQLServer.DirectoriaBackup();

            //Common.WriteToConsoleOut( "bkpdir: " + bkpdir );

            DirectoryInfo dirInfo = new DirectoryInfo(bkpdir);
            FileInfo[] filenames = dirInfo.GetFiles("*.*");

            // sort file names
            Array.Sort(filenames, (a, b) => DateTime.Compare(b.LastWriteTime, a.LastWriteTime));
            foreach (FileInfo fi in filenames)
            {
                Common.WriteToConsoleOut("{0};{1};{2};{3};{4}", fi.Name, fi.CreationTime, fi.LastWriteTime, fi.Length, fi.FullName);
                // TODO read backup file for get more info
                //read_backupfile(fi);
            }
            return;
        }
        public void lista_basesdados()
        {
            init();
            AdmBEBasesDados abds = adm.BasesDados.ListaBasesDados();
            foreach (AdmBEBaseDados bd in abds)
            {
                Common.WriteToConsoleOut("name: " + bd.get_Nome());
            }
            return;
        }
        public void config_backups()
        {
            init();
            string bkpdir = adm.SQLServer.DirectoriaBackup();

            Common.WriteToConsoleOut("DirectoriaBackup: " + bkpdir);
            return;
        }
        public void lista_planos_copiaseguranca()
        {
            init();
            AdmBEPlanosCopiasSeg lista = adm.PlanosCopiasSeguranca.ListaPlanos();
            foreach (AdmBEPlanoCopiasSeg pl in lista)
            {
                string id = pl.get_Id();
                Common.WriteToConsoleOut("PlanoCopiasSeg_id: " + id);

                string xmlPlano = pl.get_Plano();
                //Common.WriteToConsoleOut(" xml: " + xmlPlano);

                XmlReader xmlreader = XmlReader.Create(new StringReader(xmlPlano));

                //xmlreader.Read();
                xmlreader.ReadToFollowing("backupPlan");
                Common.WriteToConsoleOut(" id: " + xmlreader.GetAttribute("id"));
                Common.WriteToConsoleOut(" name: " + xmlreader.GetAttribute("name"));
                Common.WriteToConsoleOut(" verify: " + xmlreader.GetAttribute("verify"));
                Common.WriteToConsoleOut(" incremental: " + xmlreader.GetAttribute("incremental"));
                Common.WriteToConsoleOut(" overwrite: " + xmlreader.GetAttribute("overwrite"));
                Common.WriteToConsoleOut(" destination: " + xmlreader.GetAttribute("destination"));
                //Common.WriteToConsoleOut(" schedule: " + xmlreader.GetAttribute("schedule"));
                Common.WriteToConsoleOut(" date: " + xmlreader.GetAttribute("date"));
                Common.WriteToConsoleOut(" lastExecution: " + xmlreader.GetAttribute("lastExecution"));
                Common.WriteToConsoleOut(" nextExecution: " + xmlreader.GetAttribute("nextExecution"));

                string schedule_id = xmlreader.GetAttribute("schedule");
                Common.WriteToConsoleOut(" schedule id: " + schedule_id);
                AdmBECalendario pcal = adm.Calendario.Edita(schedule_id);
                //Common.WriteToConsoleOut(" schedule_id: " + pcal.Id );
                Common.WriteToConsoleOut("  schedule_periodo: " + pcal.get_Periodo().ToString());

                xmlreader.ReadToFollowing("companies");
                while (xmlreader.ReadToFollowing("company"))
                {
                    xmlreader.ReadToFollowing("properties");
                    Common.WriteToConsoleOut(" company_key: " + xmlreader.GetAttribute("key"));
                    Common.WriteToConsoleOut(" company_name: " + xmlreader.GetAttribute("name"));
                }
            }
        }
        public void insere_plano_copiaseguranca(string name, string verify, string incremental, string overwrite, string companiesByComma, string periodo)
        {
            init();
            string newid = System.Guid.NewGuid().ToString();

            AdmBEPlanoCopiasSeg newPC = new AdmBEPlanoCopiasSeg();
            AdmBECalendario objCal = new AdmBECalendario();

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

            //Common.WriteToConsoleOut(" date: " + xmlreader.GetAttribute("date"));
            DateTime datenow = DateTime.Now;
            xmlwriter.WriteAttributeString("date", datenow.ToString("dd-MM-yyyy HH:mm:ss"));

            //Common.WriteToConsoleOut(" lastExecution: " + xmlreader.GetAttribute("lastExecution"));
            DateTime lastdate = objCal.UltimaOcorrencia;
            xmlwriter.WriteAttributeString("lastExecution", lastdate.ToString("dd-MM-yyyy HH:mm:ss"));

            //Common.WriteToConsoleOut(" nextExecution: " + xmlreader.GetAttribute("nextExecution"));
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

            //Common.WriteToConsoleOut("xml string: " + stringwriter.ToString());

            //string strBackupPlan = "<backupPlan id=\"" + newpc_id + "\" name=\"teste all\" verify=\"False\" incremental=\"False\" overwrite=\"False\" destination=\"C:\\PROGRAM FILES\\MICROSOFT SQL SERVER\\MSSQL10.PRIMAVERA\\MSSQL\\BACKUP\\\" schedule=\"" + newpc_id + "\" date=\"" + DateTime.Now.ToString() + "\" lastExecution=\"undefined\" nextExecution=\"" + DateTime.Now.ToString("dd-MM-yyyy") + " 23:00:00\"><companies><company><properties key=\"OBIADM\" name=\"BIADM\"/></company><company><properties key=\"EDEMO\" name=\"PRIDEMO\"/></company><company><properties key=\"EDEMOX\" name=\"PRIDEMOX\"/></company><company><properties key=\"OPRIEMPRE\" name=\"PRIEMPRE\"/></company></companies></backupPlan>";
            newPC.set_Plano(stringwriter.ToString());

            adm.PlanosCopiasSeguranca.Actualiza(newPC);
            adm.PlanosCopiasSeguranca.ListaPlanos().Insere(newPC);

            Common.WriteToConsoleOut(" Plano de Copia Seguranca inserido com id: " + newPC.get_Id());
        }
        public void remove_plano_copiaseguranca(string id)
        {
            init();
            adm.Calendario.Remove(id);
            adm.PlanosCopiasSeguranca.Remove(id);

            Common.WriteToConsoleOut(" Plano de Copia Seguranca com id: " + id + " removido.");
        }
        public void lista_empresas()
        {
            init();
            AdmBEEmpresas empresas = adm.Empresas.ListaEmpresas(true);
            foreach (AdmBEEmpresa e in empresas)
            {
                Common.WriteToConsoleOut("name: " + e.get_Identificador() + " description: " + e.get_IDNome());
            }
            return;
        }
        public void lista_utilizadores()
        {
            init();
            StdBELista uList = adm.Consulta("SELECT * FROM utilizadores");

            uList.Inicio();
            while (!uList.NoFim())
            {
        
                CultureInfo idioma = CultureInfo.GetCultureInfo(uList.Valor("Idioma"));

                Common.WriteToConsoleOut("Utilizador: " + uList.Valor("Codigo"));
                Common.WriteToConsoleOut(" Codigo: " + uList.Valor("Codigo"));
                Common.WriteToConsoleOut(" Nome: " + uList.Valor("Nome"));
                Common.WriteToConsoleOut(" Email: " + uList.Valor("Email"));
                Common.WriteToConsoleOut(" Activo: " + uList.Valor("Activo"));
                Common.WriteToConsoleOut(" Administrador: " + uList.Valor("Administrador"));
                Common.WriteToConsoleOut(" PerfilSugerido: " + uList.Valor("PerfilSugerido"));
                Common.WriteToConsoleOut(" NaoPodeAlterarPwd: " + uList.Valor("NaoPodeAlterarPwd"));
                Common.WriteToConsoleOut(" Idioma: " + idioma);
                Common.WriteToConsoleOut(" LoginWindows: " + uList.Valor("LoginWindows"));
                Common.WriteToConsoleOut(" Telemovel: " + uList.Valor("Telemovel"));
                Common.WriteToConsoleOut(" Bloqueado: " + uList.Valor("Bloqueado"));
                Common.WriteToConsoleOut(" TentativasFalhadas: " + uList.Valor("TentativasFalhadas"));
                Common.WriteToConsoleOut(" AutenticacaoPersonalizada: " + uList.Valor("AutenticacaoPersonalizada"));
                Common.WriteToConsoleOut(" SuperAdministrador: " + uList.Valor("SuperAdministrador"));
                Common.WriteToConsoleOut(" Tecnico: " + uList.Valor("Tecnico"));

                uList.Seguinte();
            }
            return;
        }
        public void lista_perfis()
        {
            init();
            StdBELista pList = adm.Consulta("SELECT * FROM perfis");

            pList.Inicio();
            while (!pList.NoFim())
            {
                Common.WriteToConsoleOut("Perfil: " + pList.Valor("Codigo"));
                Common.WriteToConsoleOut(" Codigo: " + pList.Valor("Codigo"));
                Common.WriteToConsoleOut(" Nome: " + pList.Valor("Nome"));
                
                pList.Seguinte();
            }
            return;
        }
        public void lista_aplicacoes()
        {
            init();
            
            RegistryKey rk_LM = Registry.LocalMachine;

            string s_basepath = "SOFTWARE\\PRIMAVERA\\SGE800";
            if (Type.Equals("Executive")){
                s_basepath = "SOFTWARE\\PRIMAVERA\\SGE800";
            } else {
                s_basepath = "SOFTWARE\\PRIMAVERA\\SGP800";
            }
            RegistryKey rk_PrimaveraDefault = rk_LM.OpenSubKey(s_basepath + "\\DEFAULT");
            string[] subkeys = rk_PrimaveraDefault.GetSubKeyNames();
            foreach(string key in subkeys)
            {
                RegistryKey rk_App = rk_PrimaveraDefault.OpenSubKey(key);
                if (rk_App != null)
                {
                    string nome = (string)rk_App.GetValue("NOME");
                    if ((nome != null) && (key.Length == 3))
                    {
                        string versao = (string)rk_App.GetValue("VERSAO");
                        Common.WriteToConsoleOut("Aplicacao: " + key);
                        Common.WriteToConsoleOut(" Codigo: " + key);
                        Common.WriteToConsoleOut(" Nome: " + nome);
                        Common.WriteToConsoleOut(" Versao: " + versao);
                    }
                }
            }
            return;
        }
        public void lista_utilizador_aplicacoes(string user)
        {
            init();
            StdBELista uaList = adm.Consulta("SELECT * FROM UtilizadoresAplicacoes WHERE Utilizador='" + user + "'");

            uaList.Inicio();
            while (!uaList.NoFim())
            {
                Common.WriteToConsoleOut("Aplicacao: " + uaList.Valor("Apl"));
                uaList.Seguinte();
            }
            return;
        }
        public void insere_utilizador_aplicacao(string user, string apl)
        {
            init();
            string sqlInsereUtilizadorAplicacao = "INSERT [UtilizadoresAplicacoes] ([Utilizador], [Apl]) VALUES (N'" + user + "',N'" + apl + "')";
            adm.SQLServer.ExecutaComando(sqlInsereUtilizadorAplicacao, "PRIEMPRE", false);
            Common.WriteToConsoleOut("Insert utilizador '" + user + "' applicacao '" + apl + "' ok.");
            return;
        }
        public void remove_utilizador_aplicacao(string user, string apl)
        {
            init();
            string sqlRemoveUtilizadorAplicacao = "DELETE [UtilizadoresAplicacoes] WHERE [Utilizador] = '" + user + "' AND [Apl] = '" + apl + "'";
            adm.SQLServer.ExecutaComando(sqlRemoveUtilizadorAplicacao, "PRIEMPRE", false);
            Common.WriteToConsoleOut("Delete utilizador '" + user + "' applicacao '" + apl + "' ok.");
            return;
        }
        public bool actualiza_utilizador_aplicacoes(string user, string[] aplicacoes)
        {
            init();

            adm.IniciaTransaccao();
            try
            {
                string sqlRemoveUtilizadorAplicacoes = "DELETE [UtilizadoresAplicacoes] WHERE [Utilizador] = '" + user + "'";
                adm.SQLServer.ExecutaComando(sqlRemoveUtilizadorAplicacoes, "PRIEMPRE", false);

                foreach (string apl in aplicacoes)
                {
                    string sqlInsereUtilizadorAplicacao = "INSERT [UtilizadoresAplicacoes] ([Utilizador], [Apl]) VALUES (N'" + user + "',N'" + apl + "')";
                    adm.SQLServer.ExecutaComando(sqlInsereUtilizadorAplicacao, "PRIEMPRE", false);
                }
            }
            catch (Exception e)
            {
                adm.DesfazTransaccao();
                Common.WriteToConsoleOut("Actualiza applicacoes do utilizador '" + user + "' falhou: {0} Exception caught.", e);
                return false;
            }
            adm.TerminaTransaccao();

            Common.WriteToConsoleOut("Actualiza applicacoes do utilizador '" + user + "' ok.");
            return true;
        }
        public void lista_utilizador_permissoes(string user)
        {
            init();
            StdBELista upList = adm.Consulta("SELECT * FROM Permissoes WHERE Utilizador='" + user + "'");

            upList.Inicio();
            while (!upList.NoFim())
            {
                Common.WriteToConsoleOut("Permissao: ");
                Common.WriteToConsoleOut(" Perfil: " + upList.Valor("Perfil"));
                Common.WriteToConsoleOut(" Empresa: " + upList.Valor("Empresa"));
                upList.Seguinte();
            }
            return;
        }
        public void insere_utilizador_permissao(string user, string perfil, string empresa)
        {
            init();
            string sqlInsereUtilizadorPermissao = "INSERT [Permissoes] ([Utilizador], [Perfil], [Empresa]) VALUES (N'" + user + "',N'" + perfil + "',N'" + empresa + "')";
            adm.SQLServer.ExecutaComando(sqlInsereUtilizadorPermissao, "PRIEMPRE", false);
            Common.WriteToConsoleOut("Insert utilizador '" + user + "' permissao do perfil '" + perfil + "' empresa '" + empresa + "' ok.");
            return;
        }
        public void remove_utilizador_permissao(string user, string perfil, string empresa)
        {
            init();
            string sqlRemoveUtilizadorPermissao = "DELETE [Permissoes] WHERE [Utilizador] = '" + user + "' AND [Perfil] = '" + perfil + "' AND [Empresa] = '" + empresa + "'";
            adm.SQLServer.ExecutaComando(sqlRemoveUtilizadorPermissao, "PRIEMPRE", false);
            Common.WriteToConsoleOut("Delete utilizador '" + user + "' permissao do perfil '" + perfil + "' empresa '" + empresa + "' ok.");
            return;
        }
        public bool actualiza_utilizador_permissoes(string user, string[][] permissoes)
        {
            init();

            adm.IniciaTransaccao();
            try
            {
                string sqlRemoveUtilizadorPermissoes = "DELETE [Permissoes] WHERE [Utilizador] = '" + user + "'";
                adm.SQLServer.ExecutaComando(sqlRemoveUtilizadorPermissoes, "PRIEMPRE", false);

                
                for(int i=0; i<permissoes.Length;i++)
                {
                    if (permissoes[i].Length == 2)
                    {
                        string perfil = permissoes[i][0];
                        string empresa = permissoes[i][1];
                        string sqlInsereUtilizadorPermissao = "INSERT [Permissoes] ([Utilizador], [Perfil], [Empresa]) VALUES (N'" + user + "',N'" + perfil + "',N'" + empresa + "')";
                        adm.SQLServer.ExecutaComando(sqlInsereUtilizadorPermissao, "PRIEMPRE", false);
                    }
                }
            }
            catch (Exception e)
            {
                adm.DesfazTransaccao();
                Common.WriteToConsoleOut("Actualiza permissoes do utilizador '" + user + "' falhou: {0} Exception caught.", e);
                return false;
            }
            adm.TerminaTransaccao();

            Common.WriteToConsoleOut("Actualiza permissoes do utilizador '" + user + "' ok.");
            return true;
        }
        public void insere_utilizador(string codigo, string nome, string email, string password, string activo, string administrador, string perfilSugerido, string naoPodeAlterarPwd, string idioma, string loginWindows, string telemovel, string bloqueado, string tentativasFalhadas, string autenticacaoPersonalizada, string superAdministrador, string tecnico)
        {
            init();
            string sqlInsereUtilizador = "INSERT [Utilizadores] ([Codigo], [Nome], [Email], [Password], [Activo], [Administrador], [PerfilSugerido], [NaoPodeAlterarPwd], [Idioma], [LoginWindows], [Telemovel], [Bloqueado], [TentativasFalhadas], [AutenticacaoPersonalizada], [SuperAdministrador], [Tecnico]) VALUES (N'" + codigo + "',N'" + nome + "',N'" + email + "',N'" + password + "'," + activo + "," + administrador + ",N'" + perfilSugerido + "'," + naoPodeAlterarPwd + "," + idioma + ",N'" + loginWindows + "',N'" + telemovel + "'," + bloqueado + "," + tentativasFalhadas + "," + autenticacaoPersonalizada + "," + superAdministrador + "," + tecnico + ")";
            adm.SQLServer.ExecutaComando(sqlInsereUtilizador, "PRIEMPRE", false);
            Common.WriteToConsoleOut("Insert user '" + codigo + "' ok.");
        }
        public void actualiza_utilizador(string codigo, string nome, string email, string password, string activo, string administrador, string perfilSugerido, string naoPodeAlterarPwd, string idioma, string loginWindows, string telemovel, string bloqueado, string tentativasFalhadas, string autenticacaoPersonalizada, string superAdministrador, string tecnico)
        {
            init();
            string sqlActualizaUtilizador = "UPDATE [Utilizadores] SET [Nome] = '" + nome + "', [Email] = '" + email + "', [Activo] = " + activo + ", [Administrador] = " + administrador + ", [PerfilSugerido] = '" + perfilSugerido + "', [NaoPodeAlterarPwd] = " + naoPodeAlterarPwd + ", [Idioma] = " + idioma + ", [LoginWindows] = '" + loginWindows + "', [Telemovel] = '" + telemovel + "', [Bloqueado] = " + bloqueado + ", [TentativasFalhadas] = " + tentativasFalhadas + ", [AutenticacaoPersonalizada] = " + autenticacaoPersonalizada + ", [SuperAdministrador] = " + superAdministrador + ", [Tecnico] = " + tecnico;
            if (password.Length > 0)
            {
                sqlActualizaUtilizador = sqlActualizaUtilizador + ", [Password] = '" + password + "'";
            }
            sqlActualizaUtilizador = sqlActualizaUtilizador + " WHERE [Codigo] = '" + codigo + "'";

            //Common.WriteToConsoleOut(sqlActualizaUtilizador);

            adm.SQLServer.ExecutaComando(sqlActualizaUtilizador, "PRIEMPRE", false);
            Common.WriteToConsoleOut("Update user '" + codigo + "' ok.");
        }
        public bool remove_utilizador(string codigo)
        {
            init();

            adm.IniciaTransaccao();
            try
            {
                string sqlRemoveUtilizadorAplicacoes = "DELETE [UtilizadoresAplicacoes] WHERE [Utilizador] = '" + codigo + "'";
                adm.SQLServer.ExecutaComando(sqlRemoveUtilizadorAplicacoes, "PRIEMPRE", false);

                string sqlRemoveUtilizadorPermissao = "DELETE [Permissoes] WHERE [Utilizador] = '" + codigo + "'";
                adm.SQLServer.ExecutaComando(sqlRemoveUtilizadorPermissao, "PRIEMPRE", false);

                string sqlRemoveUtilizador = "DELETE [Utilizadores] WHERE [Codigo] = '" + codigo + "'";
                adm.SQLServer.ExecutaComando(sqlRemoveUtilizador, "PRIEMPRE", false);
            }
            catch (Exception e)
            {
                adm.DesfazTransaccao();
                Common.WriteToConsoleOut("Actualiza applicacoes do utilizador '" + codigo + "' falhou: {0} Exception caught.", e);
                return false;
            }
            adm.TerminaTransaccao();
            Common.WriteToConsoleOut("Delete user '" + codigo + "' ok.");

            return true;
        }
        public void info()
        {
            init();
            ErpBS motor = new ErpBS();

            bool _false = false;

            Common.WriteToConsoleOut("License: " + !motor.Licenca.VersaoDemo);
            Common.WriteToConsoleOut("Language: " + adm.Params.get_Idioma());
            Common.WriteToConsoleOut("Seguranca Activa: " + adm.Params.get_SegurancaActiva());
            Common.WriteToConsoleOut("Seguranca Pro Emp Activa: " + adm.Params.get_SegurancaPorEmpActiva());
            Common.WriteToConsoleOut("Modo Seguranca: " + adm.Params.get_SegurancaActiva());
            Common.WriteToConsoleOut("N Postos: " + adm.Postos.ListaPostos(ref _false).NumItens);
            Common.WriteToConsoleOut("DirectoriaBackup: " + adm.SQLServer.DirectoriaBackup());

            StdBELista uList = adm.Consulta("SELECT * FROM utilizadores");
            Common.WriteToConsoleOut("N Utilizadores: " + uList.NumLinhas());

            uList.Inicio();
            while (!uList.NoFim())
            {
                Common.WriteToConsoleOut(" Utilizador: " + uList.Valor("Codigo") + ", " + uList.Valor("Nome"));
                uList.Seguinte();
            }

            StdBELista eList = adm.Consulta("SELECT * FROM empresas");
            Common.WriteToConsoleOut("N Empresas: " + eList.NumLinhas());

            eList.Inicio();
            while (!eList.NoFim())
            {
                Common.WriteToConsoleOut(" Empresa: " + eList.Valor("Codigo") + ", " + eList.Valor("IDNome"));
                eList.Seguinte();
            }
            return;
        }
    }
}
