# Betaal Service / BC

Description
----
The Payment Component (Betalen Component) handles, preferably in collaboration with the Order Registration Component (Orderregistratie Component). To this end, it creates invoices and reports them (if desired) to a payment provider. Finally, the results of the payment provider are processed, creating an invoice overview that can be used by both the organization and customers.

If desired, the Payment Component, in collaboration with the WRC (templates), BS (mail and SMS) and the Queue Component, can be used to follow up payments in the form of reminders and exhortation.

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
For an in depth installation guide you can refer to the [installation guide](INSTALLATION.md), it also contains a short tutorial on getting your cluster ready to expose your installation to the world

Developers toolkit and technical information
----

We make our datamodels with the tool [modelio](https://www.modelio.org) which can be found along the OAS documentation and the postman collection in api/public/schema.
If you need development support we provide that through the [samenorganiseren slack channel](https://join.slack.com/t/samenorganiseren/shared_invite/zt-dex1d7sk-wy11sKYWCF0qQYjJHSMW5Q).

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
-------

Created by [Ruben van der Linde](https://www.conduction.nl/team) for conduction. But based on [api platform](https://api-platform.com) by [Kévin Dunglas](https://dunglas.fr). Commercial support for common ground components available from [Conduction](https://www.conduction.nl).
