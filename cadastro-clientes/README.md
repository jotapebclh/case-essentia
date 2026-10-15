# Cadastro de Clientes

Sistema de cadastro e gerenciamento de clientes desenvolvido em **PHP 8.2, Laravel 12 e MySQL**, utilizando arquitetura MVC.

A interface foi construída com Blade, HTML, CSS e JavaScript puro, utilizando Fetch API para realizar operações sem recarregar a página.

## Funcionalidades

- Cadastro, edição e exclusão lógica de clientes.
- Upload e visualização de fotos.
- Validação de nome, e-mail e telefone.
- Busca por nome, e-mail e telefone.
- Ordenação e paginação da listagem.

## 1. Pré-requisitos

Para executar o projeto localmente no Windows, é necessário instalar:

- **XAMPP:** fornece PHP e MySQL/MariaDB.
- **Composer:** gerenciador de dependências PHP.
- **Node.js e npm:** utilizados para instalar e compilar os arquivos CSS e JavaScript.
- **Git:** para clonar o repositório.

Versões utilizadas no desenvolvimento:

| Tecnologia | Versão |
|---|---|
| PHP | 8.2 |
| Laravel | 12 |
| MySQL/MariaDB | XAMPP |
| Composer | 2.x |
| Node.js | 24.x |

O PHP deve possuir as extensões necessárias ao Laravel, incluindo `pdo_mysql`, `mbstring`, `openssl`, `fileinfo` e `zip`.

Para executar os testes automatizados, também é necessária a extensão `pdo_sqlite`.

Caso o PHP não esteja configurado no PATH do Windows, utilize `C:\xampp\php\php.exe` no lugar de `php` nos comandos deste guia.

## 2. Clonar o projeto

Abra o PowerShell e execute:

```powershell
cd C:\xampp\htdocs
git clone https://github.com/jotapebclh/case-essentia.git
cd case-essentia\cadastro-clientes
```

O projeto Laravel está dentro da pasta `cadastro-clientes`.

## 3. Instalar as dependências

Execute os comandos abaixo na pasta do Laravel:

```powershell
composer install
npm ci OU npm.cmd ci
```

O Composer instalará as dependências PHP, enquanto o npm instalará as dependências utilizadas pelo frontend.

## 4. Configurar o ambiente

Crie o arquivo `.env` a partir do modelo disponibilizado pelo Laravel:

```powershell
Copy-Item .env.example .env
```

Gere a chave da aplicação:

```powershell
php artisan key:generate
```

Abra o arquivo `.env` e configure as informações da aplicação:

```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://127.0.0.1:8000
```

## 5. Criar e configurar o banco de dados

O projeto utiliza MySQL para armazenar os clientes.

### Criar o banco pelo phpMyAdmin

1. No XAMPP Control Panel, verifique se MySQL e Apache estão iniciados.
2. Acesse http://localhost/phpmyadmin.
3. Clique em **Novo**.
4. Informe o nome `cadastro_clientes`.
5. Selecione `utf8mb4_unicode_ci`, se disponível.
6. Clique em **Criar**.

Não é necessário criar as tabelas manualmente. Elas serão geradas pelas migrations do Laravel.

### Configurar a conexão no `.env`

No arquivo `.env`, configure:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=cadastro_clientes
DB_USERNAME=root
DB_PASSWORD=
```

Esses valores correspondem à configuração padrão mais comum do MySQL no XAMPP.

Se o seu MySQL utilizar outra porta, usuário ou senha, ajuste os valores conforme seu ambiente.

Caso exista uma variável `DB_URL` ativa no `.env`, remova-a ou deixe-a comentada para evitar que sobrescreva os parâmetros individuais da conexão.

## 6. Criar as tabelas

Após configurar o banco, execute:

```powershell
php artisan migrate
```

Esse comando executará as migrations e criará as tabelas necessárias.

## 7. Configurar o armazenamento das fotos

Execute:

```powershell
php artisan storage:link
```

O comando cria o link simbólico necessário para disponibilizar as fotos armazenadas no disco público do Laravel.

## 8. Compilar o frontend

Execute:

```powershell
npm run build OU npm.cmd run build
```

Esse comando compila os arquivos CSS e JavaScript utilizados pela aplicação.

## 9. Iniciar a aplicação

Execute:

```powershell
php artisan serve
```

O Laravel iniciará um servidor local, normalmente na porta 8000.

Acesse no navegador:

**http://127.0.0.1:8000/clientes**

A aplicação estará pronta para cadastrar e gerenciar clientes.

## 10. Executar os testes

O projeto possui testes automatizados para os principais fluxos de cadastro, edição, exclusão, validação, uploads, busca e paginação.

Execute:

```powershell
php artisan test
```

Os testes de clientes utilizam SQLite em memória e armazenamento simulado, sem modificar os dados do banco MySQL utilizado no desenvolvimento.

## 11. Estrutura do projeto

O sistema utiliza a arquitetura MVC do Laravel:

- **Model (`Cliente`):** representa os dados dos clientes e utiliza Eloquent e SoftDeletes.
- **View (Blade):** apresenta a listagem, os formulários e os modais.
- **Controller (`ClienteController`):** gerencia as operações de cadastro, consulta, edição e exclusão.
- **Form Request (`ClienteRequest`):** centraliza as regras de validação.
- **Migrations:** definem a estrutura das tabelas do banco de dados.

As fotos são armazenadas no disco público do Laravel. O banco de dados guarda apenas o caminho do arquivo.

## 12. Regras principais

- Nome, e-mail, telefone e foto são obrigatórios no cadastro.
- Na edição, a substituição da foto é opcional.
- O telefone deve possuir 10 ou 11 dígitos, incluindo DDD.
- São aceitas imagens JPEG, PNG e WebP de até 2 MB.
- O e-mail não pode estar duplicado entre clientes ativos.
- A exclusão é lógica, utilizando SoftDeletes.
- A listagem apresenta 10 clientes por página.

## 13. Problemas comuns

**Erro de conexão com o banco de dados**

Verifique se o MySQL está iniciado no XAMPP e se as informações `DB_*` do `.env` estão corretas.

**Erro relacionado à chave da aplicação**

Execute:

```powershell
php artisan key:generate
```

**Fotos não aparecem na listagem**

Verifique se o link simbólico foi criado:

```powershell
php artisan storage:link
```

**CSS ou JavaScript não carregam**

Execute novamente:

```powershell
npm run build OU npm.cmd run build
```

**Comando `php` não reconhecido**

Adicione `C:\xampp\php` ao PATH do Windows ou utilize o caminho completo do executável PHP.