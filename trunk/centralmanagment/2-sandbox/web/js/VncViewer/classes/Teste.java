//import sun.misc.BASE64Decoder;
public class Teste {
    public static void main(String[] args) {
        String s = Base64.base64Encode(args[0]);
        System.out.println("teste "+s);
    }
}
