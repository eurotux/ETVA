//
// AuthHTTPConnectSocket.java implement as HTTPConnectSocket.java
// an alternate way to connect to VNC servers via HTTP proxies
// supporting the HTTP CONNECT method with Authentication
//

import java.applet.*;
import java.net.*;
import java.io.*;

class AuthHTTPConnectSocketFactory implements SocketFactory {

  public Socket createSocket(String host, int port, Applet applet)
    throws IOException {

    return createSocket(host, port,
			applet.getParameter("PROXYHOST1"),
			applet.getParameter("PROXYPORT1"),
			applet.getParameter("PROXYUSERNAME"),
			applet.getParameter("PROXYPASSWORD")
            );
  }

  public Socket createSocket(String host, int port, String[] args)
    throws IOException {

    return createSocket(host, port,
			readArg(args, "PROXYHOST1"),
			readArg(args, "PROXYPORT1"),
			readArg(args, "PROXYUSERNAME"),
			readArg(args, "PROXYPASSWORD")
            );
  }

  public Socket createSocket(String host, int port,
			     String proxyHost, String proxyPortStr,
                 String username, String password)
    throws IOException {

    int proxyPort = 0;
    if (proxyPortStr != null) {
      try {
	proxyPort = Integer.parseInt(proxyPortStr);
      } catch (NumberFormatException e) { }
    }

    if (proxyHost == null || proxyPort == 0) {
      System.out.println("Incomplete parameter list for AuthHTTPConnectSocket");
      return new Socket(host, port);
    }

    // TODO detect authentication

    System.out.println("HTTP CONNECT via proxy " + proxyHost +
		       " port " + proxyPort);
    Socket s;
    try {
        System.out.println("Trying basic authentication ");
        s = new BasicAuthHTTPConnectSocket(host, port, proxyHost, proxyPort, username, password);
    } catch( Exception e ){
        System.out.println("Trying digest authentication ");
        s = new DigestAuthHTTPConnectSocket(host, port, proxyHost, proxyPort, username, password);
    }

    return s;
  }

  private String readArg(String[] args, String name) {

    for (int i = 0; i < args.length; i += 2) {
      if (args[i].equalsIgnoreCase(name)) {
	try {
	  return args[i+1];
	} catch (Exception e) {
	  return null;
	}
      }
    }
    return null;
  }
}

