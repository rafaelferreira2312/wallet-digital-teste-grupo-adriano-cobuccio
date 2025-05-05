# 💰 Carteira Digital - Desafio Técnico Full Stack | Grupo Adriano Cobuccio

## 🌟 Visão Geral
### Sistema completo de carteira digital com:
- ✅ Operações financeiras seguras (depósito, transferência, reversão)
- ✅ Autenticação JWT com proteção contra ataques
- ✅ Validações em tempo real e auditoria completa
- ✅ Pronto para produção com Docker e CI/CD

## 🛠️ Stack Tecnológica Atualizada
### Categoria	Tecnologias
- Backend	PHP 8.2, CodeIgniter 4.5, PHPStan (análise estática)
- Banco	MySQL 8 (com transações ACID)
- Segurança	JWT + Refresh Tokens, CORS, Rate Limiting, CSRF Protection
- Infra	Docker Compose, Nginx (proxy reverso), Redis (cache)
- Testes	PHPUnit 10 (90%+ coverage), Pest (BDD), Postman (contract tests)
- Monitoramento	Prometheus + Grafana (métricas), Sentry (erros)

## ⚙️ Instalação (Modo Docker)
```bash
git clone https://github.com/rafaelferreira2312/wallet-digital-teste-grupo-adriano-cobuccio
cd wallet-digital-teste-grupo-adriano-cobuccio
```

# 1. Configure variáveis críticas
```bash
cp .env.production .env
nano .env  
```

# 2. Inicie os serviços
```bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
```

# 3. Execute setup inicial
```bash
docker-compose exec app php spark setup --seed
```