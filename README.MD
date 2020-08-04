### DESCRIPCIÓN
API REST utilizada para buscar Personas, Peliculas, Canciones, Shows de Televisión y eBooks.

Los recursos que consulta son iTunes, TvMaze y Crcind.

### AUTORIZACIÓN
El API es de uso público, no requiere autorización.

### PARÁMETROS REQUERIDOS (BODY)
**search**: (string) | Criterio de búsqueda para los resultados.

### PARÁMETROS REQUERIDOS (HEADERS)
**Content-Type**: valores permitidos **application/json** | **application/x-www-form-urlencoded**

### CÓDIGOS DE RESPUESTA
**200**: Búsqueda Éxitosa

**204**: Registros no Encontrados

**400**: Error en parámetro(s)

**405**: Método no permitido

**500**: Error desconocido

### NODOS

**POST:** (/tribalMnC/Controllers/search.php): Recibe Headers y Body Previamente descritos

   #### Ejemplos Response Body | criterio de búsqueda: adam | Código 200

    53 resultados fueron omitidos por propósitos de muestra

    {
        "code": 200,
        "message": "Búsqueda Éxitosa",
        "data": [
            {
                "nombre": "A'dam - E.V.A.",
                "url": "http://www.tvmaze.com/shows/11739/adam-eva",
                "tipo": "TV Show",
                "fuente": "TVMaze"
            },
            {
                "nombre": "ADAM - Go To Go",
                "url": "https://music.apple.com/us/album/go-to-go/878164319?i=878164320&uo=4",
                "tipo": "Cancion",
                "fuente": "iTunes"
            },
            {
                "nombre": "Adam,Wolfgang F.",
                "url": "",
                "tipo": "Persona",
                "fuente": "Crcind"
            }        
        ],
        "totalCount": 3
    }

   #### Ejemplos Response Body | criterio de búsqueda: khjkgfdhjhkjlk | Código 204

    {
        "code": 204,
        "message": "Registros no Encontrados",
        "data": [],
        "totalCount": 0
    }

   #### Ejemplos Response Body | criterio de búsqueda: ""  (vacío) | Código 400

    {
        "code": 400,
        "message": "Criterio de búsqueda vacío."
    }

   #### Ejemplos Response Body | criterio de búsqueda: "adam"  (vacío) | método GET | Código 405

    {
        "code": 405,
        "message": "Método no permitido (GET) - solo 'POST' "
    }

   #### Ejemplos Response Body | criterio de búsqueda: adam  | Código 500 | En caso de alguna falla de comunicación o en el servidor

    {
        "code": 500,
        "message": "Ha ocurrido un error inesperado"
    }
    

### REQUERIMIENTOS DEL PROYECTO (UBUNTU)

- APACHE
- PHP 7.2 (Con extensión SOAP Activa)

#### Instalación de APACHE 

Abrir una terminal y ejecutar los siguientes comandos:

 $ sudo apt-get update

 $ sudo apt-get install apache2

La instalación solicitará autorización para instalar los paquetes, para continuar debe presiones "S" o "Y" dependiendo del idioma configurado en su Sistema Operativo.

Al terminar la instalación para verificar que el servidor está funcional puede abrir una ventana en un navegador web y dirigirse a la url http://127.0.0.1 o http://localhost (solo para servidores locales).

Fuente: https://phoenixnap.com/kb/how-to-install-apache-web-server-on-ubuntu-18-04

(Pasos 1 y 2)

#### Instalación de PHP 7.2

Abrir una terminal y ejecutar los siguientes comandos:

 $ apt-get install python-software-properties

 $ sudo add-apt-repository ppa:ondrej/php

 $ apt-get update

 $ sudo apt-get install php7.2

 $ update-alternatives --set php /usr/bin/php7.2

 $ a2enmod php7.2

 $ systemctl restart apache2

Fuente: https://www.linuxadictos.com/instalar-php-7-2-ubuntu-debian.html

#### Instalación Librería SOAP

Abrir una terminal y ejecutar los siguientes comandos:

Primero verificamos si está instalado

 $ apt-cache search php | grep -i soap

Si no está instalado procedemos con la instalación

 $ sudo apt-get install php7.2-soap

Después de instalar debemos activarlo

 $ sudo service php7.2-fpm reload


Fuente: http://www.laravelinterviewquestions.com/trick/installing-soap-module-on-php-7-ubuntu-ody/

### Enlace de Documentación POSTMAN

https://explore.postman.com/templates/11065/tribalmnc---guatemala-|-jomi-morillo
