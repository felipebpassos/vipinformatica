import { FontAwesomeIcon } from "@fortawesome/react-fontawesome";
import {
  faInstagram,
  faFacebook,
  faWhatsapp,
} from "@fortawesome/free-brands-svg-icons";
import Link from "next/link";

export default function Footer() {
  return (
    <footer id="footer" className="bg-light text-white">
      {/* Google Maps Embed */}
      <div className="w-full h-64">
        <iframe
          width="100%"
          height="100%"
          src="https://maps.google.com/maps?q=Av.%20Hip%C3%B3lito%20da%20Costa,%2078%20-%20Centro,%20Aracaju%20-%20SE,%2049097-310&t=&z=15&ie=UTF8&iwloc=&output=embed"
          title="Localização"
        />
      </div>

      {/* Footer Content */}
      <div className="max-w-7xl mx-auto px-4 py-12 grid grid-cols-1 md:grid-cols-4 gap-8">
        {/* Left Section */}
        <div>
          <Link
            href="/"
            className="text-2xl font-bold mb-4 text-gray-800 block"
          >
            <img
              src="/logo.png"
              alt="Vip Informática Logo"
              width={150}
              height={50}
            />
          </Link>
          <p className="text-gray-400 mb-8 text-sm">Inovação em cada solução</p>
          <div className="flex gap-4">
            {/* Instagram */}
            <Link
              href="https://www.instagram.com/vipltda/"
              className="bg-black rounded-full hover:opacity-75"
              style={{
                width: "35px",
                height: "35px",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
              }}
            >
              <FontAwesomeIcon
                icon={faInstagram}
                className="text-white w-6 h-6"
              />
            </Link>

            {/* Facebook (apenas o "f") */}
            <Link
              href="https://www.facebook.com"
              className="bg-black rounded-full hover:opacity-75"
              style={{
                width: "35px",
                height: "35px",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
              }}
            >
              <FontAwesomeIcon
                icon={faFacebook}
                className="text-white w-6 h-6"
              />
            </Link>

            {/* WhatsApp */}
            <Link
              href="https://wa.me/557996761012"
              className="bg-black rounded-full hover:opacity-75"
              style={{
                width: "35px",
                height: "35px",
                display: "flex",
                justifyContent: "center",
                alignItems: "center",
              }}
            >
              <FontAwesomeIcon
                icon={faWhatsapp}
                className="text-white w-6 h-6"
              />
            </Link>
          </div>
        </div>

        {/* Center Left */}
        <div className="text-gray-600">
          <ul className="space-y-2">
            <li>
              <Link href="#" className="hover:text-gray-800">
                Início
              </Link>
            </li>
            <li>
              <Link href="#" className="hover:text-gray-800">
                Serviços
              </Link>
            </li>
            <li>
              <Link href="#" className="hover:text-gray-800">
                Portfolio
              </Link>
            </li>
          </ul>
        </div>

        {/* Center Right */}
        <div className="text-gray-600">
          <ul className="space-y-2">
            <li>
              <Link
                href="https://dashboard.vipltda.com.br/login.php"
                className="hover:text-gray-800"
              >
                Minha conta
              </Link>
            </li>
            <li>
              <Link
                href="https://vipltda.com.br/chat"
                className="hover:text-gray-800"
              >
                Atendimento
              </Link>
            </li>
          </ul>
        </div>

        {/* Right Section */}
        <div className="text-gray-400 text-sm">
          <p>
            Av. Hipólito da Costa, 78 - Centro
            <br />
            Aracaju - SE, 49097-310
          </p>
          <p className="mt-4">contato@vipltda.com.br</p>
          <p className="mt-2">+55 (79) 3027-2614</p>
        </div>
      </div>

      {/* Copyright */}
      <div className="max-w-7xl mx-auto px-4 pb-8">
        <p className="text-gray-400 text-sm">
          © Todos os direitos reservados. Criado por{" "}
          <Link
            href="https://vipltda.com.br"
            className="text-gray-600 hover:text-gray-800"
          >
            Felipe Passos
          </Link>
        </p>
      </div>
    </footer>
  );
}
