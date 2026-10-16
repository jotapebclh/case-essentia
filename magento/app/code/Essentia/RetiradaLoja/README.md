# Essentia_RetiradaLoja

Módulo de entrega para Magento Open Source que disponibiliza o método
**Retirado na Loja**, com frete fixo de **R$ 0,00**, e adiciona a coluna
**Método de Entrega** com filtro à grade administrativa de pedidos.

Utiliza os mecanismos nativos de shipping carriers e de extensão da grade.
Não depende de APIs ou serviços externos de cotação e não modifica o core Magento.

## Requisitos e ambiente validado

- Magento Open Source **2.4.7-p10** instalado e configurado.
- PHP **8.3**, utilizado no ambiente de desenvolvimento e validação.
- Checkout nativo do Magento.
- Módulos nativos `Magento_Config`, `Magento_Quote`, `Magento_Sales`,
  `Magento_Shipping` e `Magento_Ui` habilitados.
- Acesso à CLI da instalação e ao painel administrativo para instalação e testes.

Os testes foram realizados em uma instalação Magento em Docker. O repositório
contém somente o módulo, sem o core Magento ou dependências externas adicionais.

## Instalação

Copie o diretório `magento/app/code/Essentia/RetiradaLoja/` deste repositório
para `app/code/Essentia/RetiradaLoja/` na instalação Magento existente.
O arquivo de registro deve ficar em:

```text
<raiz-do-magento>/app/code/Essentia/RetiradaLoja/registration.php
```

Na raiz da instalação Magento, execute com o usuário proprietário dos arquivos:

```bash
php bin/magento module:enable Essentia_RetiradaLoja
php bin/magento setup:upgrade
```

Em modo de produção, execute também a compilação de injeção de dependências:

```bash
php bin/magento setup:di:compile
```

Finalize limpando o cache e consulte o status do módulo:

```bash
php bin/magento cache:flush
php bin/magento module:status Essentia_RetiradaLoja
```

Em Docker, execute esses comandos dentro do contêiner PHP, na raiz do Magento.
A instalação não exige SQL manual, reindexação específica ou alterações no
projeto Laravel. Para atualizar uma instalação que já possui o módulo, substitua
seus arquivos e repita `setup:upgrade`, a compilação em produção e `cache:flush`.

## Configuração administrativa

O método de entrega vem **desabilitado por padrão**. Habilitar o módulo pela CLI
não habilita automaticamente o método no checkout.

1. Acesse **Stores > Configuration > Sales > Delivery Methods**
   (Lojas > Configuração > Vendas > Métodos de entrega).
2. Selecione o escopo desejado e abra a seção **Retirado na Loja**.
3. Defina **Habilitado** como **Sim**. Se necessário, desmarque a opção de
   herança de configuração para editar o valor naquele escopo.
4. Ajuste **Título**, **Nome do método** e **Ordem de exibição**, conforme necessário.
5. Salve a configuração e atualize o cache quando solicitado pelo Magento.

Os valores padrão são título **Retirado na Loja** e nome do método
**Retirada Gratuita**. O valor do frete permanece zero e não é configurável.
Configurações específicas de website/store view têm precedência sobre os
valores herdados do escopo padrão.

Também é possível habilitá-lo no escopo padrão pela CLI:

```bash
php bin/magento config:set carriers/essentia_retiradaloja/active 1
php bin/magento cache:flush
```

## Grade administrativa de pedidos

Em **Sales > Orders**, a coluna **Método de Entrega** exibe a descrição gravada
em `sales_order.shipping_description`, tanto para retirada quanto para métodos
nativos e outros carriers. Assim, o histórico preserva os títulos utilizados na
compra, mesmo que a configuração atual seja alterada. Na ausência de descrição,
exibe o código do método; pedidos sem método exibem **Sem entrega**.

