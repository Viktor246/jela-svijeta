Link to [original](https://github.com/dunglas/symfony-docker) docker container project
### Steps to run docker:  
1.  If not already done,  [install Docker Compose](https://docs.docker.com/compose/install/)  (v2.10+)
2.  Run  `docker compose build --pull --no-cache`  to build fresh images
3.  Run  `docker compose up`  (the logs will be displayed in the current shell)
4.  With a standard installation, the authority used to sign certificates generated in the Caddy container is not trusted by your local machine. You must add the authority to the trust store of the host :
	- #### Mac
		`docker cp $(docker compose ps -q caddy):/data/caddy/pki/authorities/local/root.crt /tmp/root.crt && sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain /tmp/root.crt`
	- #### Linux
		`docker cp $(docker compose ps -q caddy):/data/caddy/pki/authorities/local/root.crt /usr/local/share/ca-certificates/root.crt && sudo update-ca-certificates`
	- #### Windows
		`docker compose cp caddy:/data/caddy/pki/authorities/local/root.crt %TEMP%/root.crt && certutil -addstore -f "ROOT" %TEMP%/root.crt`
		
5.  Run  `docker compose down --remove-orphans`  to stop the Docker containers.

### Loading dummy data into databse

To load dummy data into database run following command

- `docker-compose exec php bin/console doctrine:fixtures:load`

To modify supported languages and amount of data loaded, parameters inside `config/services.yaml` are provided
		
