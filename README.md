# betaalservice
[![StyleCI](https://github.styleci.io/repos/206145303/shield?branch=master)](https://github.styleci.io/repos/206145303?branch=master)
[![Docker Image CI](https://github.com/ConductionNL/betaalservice/workflows/Docker%20Image%20CI/badge.svg?branch=master)](https://github.com/ConductionNL/betaalservice/actions?query=workflow%3A"Docker+Image+CI")
[![Artifacthub](https://img.shields.io/endpoint?url=https://artifacthub.io/badge/repository/betaalservice)](https://artifacthub.io/packages/helm/betaalservice/betaalservice)
[![BCH compliance](https://bettercodehub.com/edge/badge/ConductionNL/betaalservice?branch=master)](https://bettercodehub.com/)
[![Componenten Catalogus](https://img.shields.io/badge/vng--componentencatalogus-posted-green)](https://componentencatalogus.commonground.nl/componenten/180?)
[![Status badge](https://shields.api-test.nl/endpoint.svg?style=for-the-badge&url=https%3A//api-test.nl/api/v1/provider-latest-badge/062a1a37-3fb2-49ca-a0d8-8c80b5ba891d/)](https://api-test.nl/server/4/99c117bb-6732-459a-86cb-f9c1ca313913/062a1a37-3fb2-49ca-a0d8-8c80b5ba891d/latest/)

Description
----
Het Betalen Component handelt, bij voorkeur in samenwerking met het Order Registratie Component, betalingen af. Hiervoor maakt het facturen aan en meldt deze (indien gewenst) bij een payment provider aan. Als laatste worden de resultaten van de payment provider verwerkt, waarmee een invoice overzicht wordt gecreëerd dat kan worden gebruikt door zowel organisaties als klanten.

Indien gewenst kan het Betalen Component in samenwerking met het WRC (sjablonen), BS (mail en sms) en het Queue Component worden ingezet voor de opvolging van betalingen in de trant van herinneringen en aanmaningen.

Additional Information
----

- [Contributing](CONTRIBUTING.md)
- [ChangeLogs](CHANGELOG.md)
- [RoadMap](ROADMAP.md)
- [Security](SECURITY.md)
- [Licence](LICENSE.md)


Installation
----
We differentiate between two way's of installing this component, a local installation as part of the provided developers toolkit or an [helm](https://helm.sh/) installation on an development or production environment.

#### Local installation
First make sure you have [docker desktop](https://www.docker.com/products/docker-desktop) running on your computer. Then clone the repository to a directory on your local machine through a [git command](https://github.com/git-guides/git-clone) or [git kraken](https://www.gitkraken.com) (ui for git). If successful you can now navigate to the directory of your cloned repository in a command prompt and execute docker-compose up.
```CLI
$ docker-compose up
```
This will build the docker image and run the used containers and when seeing the log from the php container: "NOTICE: ready to handle connections", u are ready to view the documentation at localhost on your preferred browser.

#### Instalation on Kubernetes or Haven
As a haven compliant commonground component this component is installable on kubernetes trough helm. The helm files can be found in the api/helm folder. For installing this component trough helm simply open your (still) favorite command line interface and run
```CLI
$ helm install [name] ./api/helm --kubeconfig kubeconfig.yaml --namespace [name] --set settings.env=prod,settings.debug=0,settings.cache=1
```
For an in depth installation guide you can refer to the [installation guide](/api/helm) contained with the helm files, it also contains a short tutorial on getting your cluster ready to expose your installation to the world

Standards
----

This component adheres to international, national and local standards (in that order), notable standards are:

- Any applicable [W3C](https://www.w3.org) standard, including but not limited to [rest](https://www.w3.org/2001/sw/wiki/REST), [JSON-LD](https://www.w3.org/TR/json-ld11/) and [WEBSUB](https://www.w3.org/TR/websub/)
- Any applicable [schema](https://schema.org/) standard
- [OpenAPI Specification](https://github.com/OAI/OpenAPI-Specification/blob/master/versions/3.0.0.md)
- [GAIA-X](https://www.data-infrastructure.eu/GAIAX/Navigation/EN/Home/home.html)
- [Publiccode](https://docs.italia.it/italia/developers-italia/publiccodeyml-en/en/master/index.html), see the [publiccode](api/public/schema/publiccode.yaml) for further information
- [Forum Stanaardisatie](https://www.forumstandaardisatie.nl/open-standaarden)
- [NL API Strategie](https://docs.geostandaarden.nl/api/API-Strategie/)
- [Common Ground Realisatieprincipes](https://componentencatalogus.commonground.nl/20190130_-_Common_Ground_-_Realisatieprincipes.pdf)
- [Haven](https://haven.commonground.nl/docs/de-standaard)
- [NLX](https://docs.nlx.io/understanding-the-basics/introduction)
- [Standard for Public Code](https://standard.publiccode.net/), see the [compliancy scan](publiccode.md) for further information.

This component is based on the following [schema.org](https://schema.org) sources:
- [Address](https://schema.org/PostalAddress)
- [Person](https://schema.org/Person)

Developers toolkit and technical information
----
You can find the data model, OAS documentation and other helpfull developers material like a  postman collection under api/public/schema, development support is provided trough the [samenorganiseren slack channel](https://join.slack.com/t/samenorganiseren/shared_invite/zt-dex1d7sk-wy11sKYWCF0qQYjJHSMW5Q).

Couple of quick tips when you start developing
- If you not yet have setup the component locally read the Tutorial for setting up your local environment.
- You can find the other components on [Github](https://github.com/ConductionNL).
- Take a look at the [commonground componenten catalogus](https://componentencatalogus.commonground.nl/componenten?) to prevent development collitions.
- Use [Commongroun.conduction.nl](https://commonground.conduction.nl/) for easy deployment of test environments to deploy your development to.
- For information on how to work with the component you can refer to the tutorial [here](TUTORIAL.md).


Contributing
----
First of al please read the [Contributing](CONTRIBUTING.md) guideline's ;)

But most imporantly, welcome! We strife to keep an active community at [commonground.nl](https://commonground.nl/), please drop by and tell is what you are thinking about so that we can help you along.


Credits
----
Information about the authors of this component can be found [here](AUTHORS.md)

Copyright © [Utrecht](https://www.utrecht.nl/) 2019
