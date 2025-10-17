1- URL Base da API
Todas as requisições devem ser feitas para o seguinte domínio base: https://api.genesys.finance

Importante
Todos os endpoints documentados devem ser anexados a esta URL base. Por exemplo, para criar uma transação, você deve fazer uma requisição para https://api.genesys.finance/v1/transactions.

2- Autenticação
A autenticação é feita através do API Secret nos cabeçalhos da requisição: 
api-secret: sk_987ae172053d398271afa880e0f8ed7740a58b4094867beda43213bd3d28079452a681290c6e525741046b28fe52eff118361a3cb41cc92ebf01e4a7c3e90733

3- Consultar informações da conta
Este endpoint permite consultar as informações da vinculada a chave de API.

GET
https://api.genesys.finance/v1/account-info

Resposta:
{
"email": "jonhdoe@email.com",
"name": "Jonh doe",
"document": "12345678912"
}

4 - Consultar Transação:
Este endpoint permite consultar os detalhes de uma transação previamente criada.

GET
https://api.genesys.finance/v1/transactions/:transaction_id

Parâmetros de URL
Parâmetro	    Tipo	Descrição
transaction_id	string	ID único da transação que deseja consultar

Resposta:
{
"id": "c22dc7e1-8b10-4580-9dc4-ebf78ceca475",
"external_id": null,
"status": "PENDING",
"amount": 10,
"payment_method": "PIX",
"customer": {
  "name": "Jon doe sudo",
  "email": "niriv77914@dwriters.com",
  "phone": "00000000000",
  "document": "24125439095",
  "address": {
    "cep": "32323232",
    "city": "Florianopolis",
    "state": "SC",
    "number": "82",
    "street": "Florianopolis Centro",
    "complement": "ALTOS",
    "neighborhood": "Centro"
  }
},
"created_at": "2025-04-03T20:45:33.855Z"
}

5- Criar Transação:
Este endpoint permite criar uma nova transação em nosso sistema.

POST
https://api.exemplo.com/v1/transactions

Corpo da Requisição:

{
"external_id": "string",
"total_amount": number,
"payment_method": "PIX",
"webhook_url": "string",
"items": [
  {
    "id": "string",
    "title": "string",
    "description": "string",
    "price": number,
    "quantity": number,
    "is_physical": boolean
  }
],
"ip": "string",
"customer": {
  "name": "string",
  "email": "string",
  "phone": "string",
  "document_type": "CPF" | "CNPJ",
  "document": "string"
  "utm_source": "string"
  "utm_medium": "string"
  "utm_campaign": "string"
  "utm_content": "string"
  "utm_term": "string"
},
"splits": [
  {
    "recipient_id": "string",
    "percentage": number
  }
]
}

6- Resposta
A API retorna um objeto JSON com os detalhes da transação criada:

{
"id": "string",
"external_id": "string",
"status": "AUTHORIZED" | "PENDING" | "CHARGEBACK" | "FAILED" | "IN_DISPUTE",
"total_value": number,
"customer": {
  "email": "string",
  "name": "string"
},
"payment_method": "string",
"pix": {
  "payload": "string"
},
"hasError": boolean
}


Status da Transação
Os possíveis valores para o campo status são:

PENDING - Aguardando pagamento
AUTHORIZED - Pagamento aprovado
FAILED - Pagamento falhou
CHARGEBACK - Estorno solicitado
IN_DISPUTE - Em disputa

7- Webhook
Notificações de mudança de status são enviadas para a URL configurada:

{
"id": "string",
"external_id": "string",
"total_amount": number,
"status": "AUTHORIZED" | "PENDING" | "CHARGEBACK" | "FAILED" | "IN_DISPUTE",
"payment_method": "string"
}


