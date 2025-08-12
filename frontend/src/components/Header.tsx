"use client";
import { useState } from "react";
import Link from "next/link";

export default function Header() {
  const [isMenuOpen, setIsMenuOpen] = useState(false);

  return (
    <header className="bg-white shadow sticky top-0 z-50">
      <nav className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between h-16 items-center">
          {/* Logo */}
          <Link href="/">
            <img
              src="/logo.png"
              alt="Vip Informática Logo"
              width={150}
              height={50}
            />
          </Link>

          {/* Links de navegação (Desktop) */}
          <div className="hidden md:flex items-center gap-0">
            <div className="flex gap-3">
              <Link
                href="#servicos"
                className="px-4 py-2 rounded-md hover:bg-gray-200 active:bg-gray-300 transition text-gray-600"
              >
                Serviços
              </Link>
              <Link
                href="/portfolio"
                className="px-4 py-2 rounded-md hover:bg-gray-200 active:bg-gray-300 transition text-gray-600"
              >
                Portfolio
              </Link>
              <Link
                href="#footer"
                className="px-4 py-2 rounded-md hover:bg-gray-200 active:bg-gray-300 transition text-gray-600"
              >
                Contato
              </Link>
            </div>

            <div className="flex gap-4 ml-8">
              <Link
                href="https://dashboard.vipltda.com.br/login.php"
                className="bg-gray-800 text-white px-4 py-2 rounded-md hover:bg-gray-900 transition"
              >
                Minha conta
              </Link>
            </div>
          </div>

          {/* Botão de Toggle (Mobile) */}
          <button
            onClick={() => setIsMenuOpen(!isMenuOpen)}
            className="md:hidden p-2 rounded-md text-gray-600 hover:bg-gray-100 focus:outline-none"
            aria-label="Toggle menu"
            aria-expanded={isMenuOpen}
          >
            <svg
              className="h-6 w-6 transition-transform duration-200"
              viewBox="0 0 24 24"
            >
              {/* Ícone de Hambúrguer */}
              <path
                className={`transition-opacity duration-200 ${
                  isMenuOpen ? "opacity-0" : "opacity-100"
                }`}
                fill="currentColor"
                d="M4 6h16v2H4zm0 5h16v2H4zm0 5h16v2H4z"
              />
              {/* Ícone de X */}
              <path
                className={`transition-transform duration-200 ${
                  isMenuOpen ? "opacity-100" : "opacity-0"
                }`}
                fill="currentColor"
                d="M19 6.41L17.59 5 12 10.59 6.41 5 5 6.41 10.59 12 5 17.59 6.41 19 12 13.41 17.59 19 19 17.59 13.41 12z"
              />
            </svg>
          </button>
        </div>

        {/* Menu Mobile */}
        <div
          className={`md:hidden ${
            isMenuOpen ? "block" : "hidden"
          } pb-4 transition-all duration-300`}
        >
          <div className="flex flex-col space-y-2">
            <Link
              href="#servicos"
              className="px-4 py-2 rounded-md hover:bg-gray-100 text-gray-600"
              onClick={() => setIsMenuOpen(false)}
            >
              Serviços
            </Link>
            <Link
              href="/portfolio"
              className="px-4 py-2 rounded-md hover:bg-gray-100 text-gray-600"
              onClick={() => setIsMenuOpen(false)}
            >
              Portfolio
            </Link>
            <Link
              href="#footer"
              className="px-4 py-2 rounded-md hover:bg-gray-100 text-gray-600"
              onClick={() => setIsMenuOpen(false)}
            >
              Contato
            </Link>
            <Link
              href="https://dashboard.vipltda.com.br/login.php"
              className="px-4 py-3 rounded-md bg-gray-800 text-white text-center hover:bg-gray-900 mt-2"
              onClick={() => setIsMenuOpen(false)}
            >
              Minha conta
            </Link>
          </div>
        </div>
      </nav>
    </header>
  );
}
