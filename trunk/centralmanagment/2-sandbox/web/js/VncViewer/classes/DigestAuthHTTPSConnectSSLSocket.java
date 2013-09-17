//
// DigestAuthHTTPSConnectSSLSocket.java implement as HTTPSConnectSSLSocket.java
// an alternate way to connect to VNC servers via HTTPS proxies
// supporting the HTTPS CONNECT method with Authentication
//

import javax.net.ssl.*;
import java.net.*;
import java.io.*;

class DigestAuthHTTPSConnectSSLSocket {

  public static SSLSocket connectSSLSocket(String host, int port,
                               String proxyHost, int proxyPort,
                                String username, String password)
    throws IOException {

    // Connect to the specified HTTP proxy
    SSLSocketFactory sf = (SSLSocketFactory) SSLSocketFactory.getDefault();
    SSLSocket ss = (SSLSocket)sf.createSocket(proxyHost, proxyPort);

    ss.getOutputStream().write(("CONNECT " + host + ":" + port +
                     " HTTP/1.0" + "\r\n" +
                     "Proxy-Authorization: Basic " + 
                            Base64.base64Encode(
                                username + ":" + password
                            ) +
                     "\r\n").getBytes());

    // Read the first line of the response
    DataInputStream is = new DataInputStream(ss.getInputStream());
    String str = is.readLine();

    // Check the HTTP error code -- it should be "200" on success
    if (!str.startsWith("HTTP/1.0 200 ")) {
      if (str.startsWith("HTTP/1.0 "))
        str = str.substring(9);
      throw new IOException("Proxy reports \"" + str + "\"");
    }

    // Success -- skip remaining HTTP headers
    do {
      str = is.readLine();
    } while (str.length() != 0);

    return ss;
  }
}

