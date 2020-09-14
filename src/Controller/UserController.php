<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Doctrine\Common\Collections\ArrayCollection;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Validation;
use App\Services\JwtAuth;
use Symfony\Component\HttpFoundation\JsonResponse;

class UserController extends AbstractController
{

    private function resJson($data)
    {
        // Serializar datos con servicios de serializacion
        $json = $this->get('serializer')->serialize($data, 'json');

        //Response con hhtp foundation
        $response = new Response();

        //Asignarle contenido de la respuesta
        $response->setContent($json);

        //indicar el contenido de la respuesta
        $response->headers->set('Content-Type', 'application/json');

        //devolver la respuesta
        return $response;
    }
    public function index(UserRepository $userRepository)
    {

        $users = new ArrayCollection();
        $users = $userRepository->findAll();

        $user = $userRepository->find(1);

        return $this->resJson($user);
    }

    public function create(Request $request, UserRepository $userRepository)
    {
        //Recoger los datos por get o post
        $json = $request->get('json', null);

        //Decodificar el json
        $params = json_decode($json);

        //Respuesta por defecto
        $data = array(
            'status' => 'error',
            'code' => 200,
            'message' => 'El usuario no se ha creado.',
            'json' => $params
        );

        //Comprobar y validar datos
        if ($json != null) {
            $name = (!empty($params->name)) ? $params->name : null;
            $surname = (!empty($params->surname)) ? $params->surname : null;
            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;

            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);


            if (!empty($email) && count($validate_email) == 0 && !empty($password) && !empty($name) && !empty($surname)) {
                //Si la validacion es correcta, crear el objeto usuario

                $user = new User();
                $user->setName($name);
                $user->setSurname($surname);
                $user->setEmail($email);
                $user->setRole('ROLE_USER');
                $user->setCreatedAt(new \DateTime('now'));

                //Cifrar la contraseña
                $pwd = hash('sha256', $password);
                $user->setPassword($pwd);

                $data = $user;

                //Comprobar si el usuario existe (duplicados)
                $doctrine = $this->getDoctrine();
                $em = $doctrine->getManager();

                $issetUser = $userRepository->findBy(array(
                    "email" => $email
                ));


                //si no existe, guardarlo en la bd

                if (count($issetUser) == 0) {
                    //guardo el usuario

                    $em->persist($user);
                    $em->flush();

                    $data = array(
                        'status' => 'success',
                        'code' => 200,
                        'message' => 'Usuario creado correctamente',
                        'user' => $user
                    );
                } else {
                    $data = array(
                        'status' => 'error',
                        'code' => 400,
                        'message' => 'Usuario duplicado'
                    );
                }
            }
        }

        //Hacer respuesta en json
        return $this->resJson($data);
    }

    public function login(Request $request, JwtAuth $jwt_auth)
    {
        //Recibir los datos por post
        $json = $request->get('json', null);
        $params = json_decode($json);

        //Array de datos por defecto
        $data = array(
            "status" => "error",
            "code" => "200",
            "message" => "Usuario no se ha podido identificar"
        );

        //Comprobar y validar los datos
        if ($json != null) {

            $email = (!empty($params->email)) ? $params->email : null;
            $password = (!empty($params->password)) ? $params->password : null;
            $getToken = (!empty($params->getToken)) ? $params->getToken : null;


            $validator = Validation::createValidator();
            $validate_email = $validator->validate($email, [
                new Email()
            ]);

            if (!empty($email) && !empty($password) && count($validate_email) == 0) {

                //Cifrar la contraseña
                $pwd = hash('sha256', $password);
                
                //Si todo es valido llamaremos al servicio para identificar al usuario 
                if($getToken){
                    $signup = $jwt_auth->signIn($email, $pwd, $getToken);
                }else{
                    $signup = $jwt_auth->signIn($email, $pwd);
                }
                
                return new JsonResponse($signup);

            }
        }
        //Si nos devuelve bien todo, haremos una respuesta
        return $this->resJson($data);
    }

    public function edit(Request $request, JwtAuth $jwt_auth){

        //recoger la cabecera de autentificacion
        $token = $request->headers->get('Authorization');
        
        //crear un metodo para comprobar si el token es correcto
        $checkToken = $jwt_auth->checkToken($token);

        //si es correcto hacer la actualizacion del usuario
        if($checkToken){
            //Actualizar al usuario

            //Conseguir el entity manager
            $em = $this->getDoctrine()->getManager();

            //Conseguir los datos del usuario identificado
            $identity = $jwt_auth->checkToken($token, true);

            //Respuesta por defecto
            $data = [
                "status" => "error",
                "code" => "400",
                "message" => "Usuario no actualizado",
            ];

            //Conseguir el usuario a actualizar completo
            $userRepository = $this->getDoctrine()->getRepository(User::class);
            $user = $userRepository->findOneBy([
                'id' => $identity->sub
            ]);

            //Recoger los datos por post
            $json = $request->get('json', null);
            $params = json_decode($json);


            //Comprobar y validar los datos
            if(!empty($json)){
                $name = (!empty($params->name)) ? $params->name : null;
                $surname = (!empty($params->surname)) ? $params->surname : null;
                $email = (!empty($params->email)) ? $params->email : null;
    
                $validator = Validation::createValidator();
                $validate_email = $validator->validate($email, [
                    new Email()
                ]);
    
    
                if (!empty($email) && count($validate_email) == 0 && !empty($name) && !empty($surname)) {

                    //Asignar nuevos datos al objeto del usuario
                    $user->setEmail($email);
                    $user->setName($name);
                    $user->setSurname($surname);

                    //Comprobar duplicados
                    $issetUser = $userRepository->findBy([
                        "email" => $email
                    ]);

                    if(count($issetUser) == 0 || $identity->email == $email){

                        //Guardar cambios en la base de datos
                        $em->persist($user);
                        $em->flush();

                        $data = [
                            "status" => "success",
                            "code" => "200",
                            "message" => "Usuario actualizado",
                            "user" => $user
                        ];
                    }else{
                        $data = [
                            "status" => "error",
                            "code" => "400",
                            "message" => "No puedes usar ese mail",
                        ];
                    }
                }
            }
        }
        //..
        return $this->resJson($data);
    }

    
}
