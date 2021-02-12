# Standard for Public Code
We want our project to be well-structured, and we want to be able to explain why it is.
In order to develop the best project we possibly can and to meet our requirements, we adhere to the standard of public code:

[Standard for Public Code](https://standard.publiccode.net)

## Criteria
To meet the criteria of the standard of public code, we compare our criteria to those of the public code standard.

### Code in the open
One of the first things we do when creating a new component, is making sure the github repository is set to public viewing access (just like we did with this one). This way we can give anyone access to use or improve our code. Besides this we publicly show as much information as we can about every repository with various files just like this one. More about this can be found under [Welcome Contributors](#welcome-contributers).  

### Bundle policy and source code
For our policy that all the source code is based on we use a few standards, such as [W3C](https://www.w3.org) and [schema](https://schema.org/). More about our standards can be found in the [README](README.md). 

### Create reusable and portable code
For reusability and portability of our code we have of course set any code that is reusable to public access, but we also created the so called [proto component](https://github.com/ConductionNL/Proto-component-commonground) which is a foundation on which all our components are based, and an [example component](https://github.com/ConductionNL/commonground-example) that can be used by anyone to make their own component. Not only do we give people the ability to reuse and improve upon our code this way, but also the option to update and improve the base foundation of their components if the proto component is changed. Furthermore, we included an easy to find publiccode.yaml file in the root of our repositories with more info about this component.

### Welcome contributors
To welcome contributors we made sure to make a very clear [CONTRIBUTING](CONTRIBUTING.md) and [README](README.md) file in which we explain how to start using or add code to this component. We also have a public [ROADMAP](ROADMAP.md) file where we can inform new contributors of changes that are already planned. 

### Make contributing easy
To make contributing as easy as possible we created an [example github repository](https://github.com/ConductionNL/commonground-example), that anyone can use to create their own commonground component just like this one. We also have a [dashboard application](https://commonground.conduction.nl/) that anyone can use to deploy their own created commonground components, or the ones created by other organizations/people.

### Maintain version control
In order to maintain version control we first of all make use of a production and development branch to make a clear difference there between these versions. Furthermore, all components have a version and major version in their root .env files and this version is shown in the redoc. But this is a point where we could improve, because we have not been updating these versions consistently.

### Require review of contributions
We always assign a reviewer to any requests for code changes. Besides the automated tests we run on the requested modifications or additions we make sure to always take a closer look to the requested changes, and the results of these tests when reviewing. 

### Document your objectives
To create a clear picture of what this component is for, we describe how and what this component is used for in the [README](README.md). Besides this we also have a [ROADMAP](ROADMAP.md) file in which we can document any upcoming adjustments or future plans.

### Use plain English
During development we always write our documentation in English and we make sure to create file and class names in american english.

### Use open standards
We use a number of standards as mandatory, see the [README](README.md) for these standards we use.

### Use continuous integration
To quickly identify problems and reduce risks during development we make use of github workflow and include automated tests such as StyleCI, Better Code Hub and a Postman Collection.

### Publish with an open license
We publish all our software under the [EUPL (European Union Public Licence)](https://joinup.ec.europa.eu/collection/eupl/introduction-eupl-licence) Licence, which is one of the OSI-approved open source licences. There is a [LICENSE](LICENSE) file present in the root of our repositories with a copyright notice. 

### Use a coherent style
To adhere a coding and writing style we use the already existing [StyleCI](https://styleci.io/). We are using StyleCi for automated test on our coding style.

### Document codebase maturity
In order to document the maturity of our codebases we use a stable and ready to use production branch (master), a main development branch for development and testing with multiple sub-development branches (dev-x) to avoid code conflicts. Any dependencies on other repositories are always on their production branch. These production branches are always the place to get the most up to date stable version of our repository.
