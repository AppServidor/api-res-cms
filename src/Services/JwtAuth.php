<?php

namespace App\Services;

use Firebase\JWT\JWT;
use App\Entity\User;

class JwtAuth{

    public $manager;
    public $key;

    public function __construct($manager)
    {
        $this->manager = $manager;
        $this->key = 'hola_que_tal5188151848';
    }

    public function signIn($email, $password, $getToken = null){
        
        //Comprobar si el usuario existe
        $user = $this->manager->getRepository(User::class)->findOneBy([
            'email' => $email,
            'password' => $password
        ]);

        $signup = false;
        if(is_object($user)){
            $signup = true;
        }

        //Si existe, generar el token de jwt
        if($signup){

            $token = [
                'sub' => $user->getId(),
                'name' => $user->getName(),
                'surname' => $user->getSurname(),
                'email' => $user->getEmail(),
                'iat' => time(),
                'exp' => time() + (7*24*60*60)
            ];


            $jwt = JWT::encode($token, $this->key, 'HS256');

            //Comprobar el flag getToken
            if(!empty($getToken)){
                //Devolvemos el token
                $data = $jwt;   
            }else{
                //Devolvemos el usuario
                $decoded = JWT::decode($jwt, $this->key, ['HS256']);
                $data = $decoded;   
            }

            
        }else{
            $data = [
                'status' => 'error',
                'message' => 'Login incorrecto'
            ];
        }


        //Devolver los datos
        return $data;
    }

    public function checkToken($token, $identity = false){

        $auth = false;

        //Intentamos decodificar el objeto JWT que trae el token
        try{
            $decoded = JWT::decode($token, $this->key, ['HS256']);
        
        //Si se producen excepciones auth = false    
        }catch(\UnexpectedValueException $e){
            $auth = false;
        }catch(\DomainException $e){
            $auth = false;
        }
        
      
        //Comprobamos que los datos decodificados sean correctos
        if(isset($decoded) && !empty($decoded) && is_object($decoded) && isset($decoded->sub)){
            $auth = true;
        }else{
            $auth = false;
        }

        if($identity){
            //($identity = false)devolvemos el objeto usuario (formato JWT)
            return $decoded;
        }else{
            //Devolvemos un true
            return $auth;
        }
    }
}