# 🎉 GoodParty

**GoodParty** é uma plataforma tecnológica desenvolvida para modernizar e facilitar a operação de **casas de shows, eventos e profissionais do entretenimento**.

Nosso objetivo é oferecer uma solução completa, prática e eficiente para o setor de eventos, conectando clientes, estabelecimentos e profissionais através de um único app.

## 🚀 Funcionalidades

- 🎟️ Venda de ingressos online
- 🛒 Venda de produtos diretamente pelos estabelecimentos
- 📊 Ferramentas de análise de desempenho e relatórios inteligentes
- 🤝 Conexão entre garçons e casas de festas para contratação
- 💸 Sistema de pagamento simplificado e rápido para garçons
- 🧠 Recursos de administração e gestão de negócios

## 📱 Sobre o App

A plataforma é acessível via aplicativo mobile e web, com foco na experiência do usuário, segurança nas transações e agilidade nas operações do evento.

## 👨‍💻 Tecnologias utilizadas

<p align="left">
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/vuejs/vuejs-original.svg" height="40" alt="Vue.js"/>
  <img src="https://nuxt.com/assets/design-kit/icon-green.svg" height="40" alt="Nuxt.js"/>
  <img src="https://img.icons8.com/fluent/512/node-js.png" height="40" alt="Node.js"/>
  <img src="https://cdn.jsdelivr.net/gh/devicons/devicon/icons/mysql/mysql-original.svg" height="40" alt="MySQL"/>
</p>

## 💼 Contato

Para parcerias, sugestões ou dúvidas:
----
----
----

> A GoodParty é uma startup brasileira focada em tecnologia para o entretenimento. Nosso propósito é fazer com que a experiência de festas e eventos seja simples, divertida e rentável para todos os envolvidos.
## 🚀 Laravel Docker Octane Starter

Este projeto utiliza um setup Docker otimizado com Alpine, PHP 8.3, Swoole, Octane e Nginx.

### Comandos rápidos
- **Iniciar:** `docker compose up -d`
- **Octane com Watch:** `docker exec -it goodparty-app php artisan octane:start --watch`
- **Testes de Infra:** `docker exec -it goodparty-app php artisan test --filter=InfraTest`
- **Logs Nginx:** `./logs/nginx/access.log`

### Workflow Reutilizável
O setup pode ser replicado usando o workflow em `.agent/workflows/laravel-docker-octane-starter.md`.
### Como utilizar o starter em novos projetos

Para replicar esta infraestrutura em um novo projeto:
1. Copie a pasta `.agent/workflows/` para a raiz do seu novo workspace.
2. Invoque o workflow via comando `/laravel-docker-octane-starter`.
3. O agente irá configurar automaticamente o Dockerfile, compose.yml, Nginx e os testes de infraestrutura no novo ambiente.
