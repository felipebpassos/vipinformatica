# Vip Informática - Sistema de Chamados

Este projeto é composto por duas partes principais:

- **Site institucional** desenvolvido com **Next.js**
- **Painel de gestão de chamados** (ordem de serviço) desenvolvido em **PHP + MySQL**

## 🔹 Site Institucional

Tecnologia: **Next.js** (React)

### Scripts disponíveis:

- `npm run dev` – Inicia o servidor de desenvolvimento
- `npx next build` – Compila a versão estática do site para produção

O site é exportável e pode ser hospedado como arquivos estáticos. A página **/chat** simula um atendimento com chatbot, que guia o cliente por um **formulário dinâmico** de abertura de chamado.

---

## 🔹 Painel de Chamados

Tecnologias: **PHP puro** (sem framework) e **MySQL**

### Funcionalidades por nível de acesso:

#### 👤 Cliente
- Abertura de chamados (ordens de serviço)
- Acompanhamento do status dos seus chamados
- Acesso via painel ou site institucional (via `/chat`)

#### 🛠️ Técnico
- Visualização dos chamados atribuídos
- Cadastro e edição de informações nos chamados
- Sem permissão para exclusões

#### 👨‍💼 Administrador
- Controle total do sistema
- Acompanhamento de todos os chamados
- Gestão de usuários e permissões
- Acesso a auditoria de ações realizadas no sistema

---

## ✉️ Envio de E-mails

O sistema realiza **envio automático de e-mails** em eventos importantes, como:
- Confirmação de abertura de chamado
- Atualizações de status
- Encerramento do chamado

---

## 📂 Banco de Dados

O sistema utiliza **MySQL** como banco de dados relacional, com tabelas estruturadas para controle de usuários, permissões, chamados, e log de auditoria.

---

## 🏁 Considerações finais

Este software foi desenvolvido exclusivamente para a **Vip Informática**, visando melhorar o fluxo de atendimento técnico e facilitar o contato entre clientes e equipe técnica.

