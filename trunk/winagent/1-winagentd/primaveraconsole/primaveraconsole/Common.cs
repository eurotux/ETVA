using System;
using System.Collections.Generic;
using System.Linq;
using System.Text;

namespace primaveraconsole
{
    class Common
    {
        public static void WriteToConsoleOut(string s)
        {
            Console.WriteLine(System.Net.WebUtility.HtmlEncode(s));
        }
        public static void WriteToConsoleOut(string fmt, params object[] args)
        {
            Console.WriteLine(String.Format(fmt, args));
        }
    }
}
