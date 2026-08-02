<?php


namespace App\Service\Tools;

use App\Entity\Department;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use GuzzleHttp\Client;
use Stevenmaguire\OAuth2\Client\Provider\Microsoft;

class UserManagerAzure
{
    private EntityManagerInterface $entityManager;
    private string $codeSite;
    private string $azureClientId;
    private string $azureClientSecret;
    private string $azureRedirectUri;
    private string $azureUrlAuthorize;
    private string $azureUrlAccessToken;
    private string $azureUrlResourceOwnerDetails;
    private string $tenant;

    public function __construct(
        EntityManagerInterface $entityManager,
        string                 $codeSite,
        string                 $azureClientId,
        string                 $azureClientSecret,
        string                 $azureRedirectUri,
        string                 $azureUrlAuthorize,
        string                 $azureUrlAccessToken,
        string                 $azureUrlResourceOwnerDetails,
        string                 $tenant = 'common' // valeur par défaut
    )
    {
        $this->entityManager = $entityManager;
        $this->codeSite = strtolower($codeSite);
        $this->azureClientId = $azureClientId;
        $this->azureClientSecret = $azureClientSecret;
        $this->azureRedirectUri = $azureRedirectUri;
        $this->azureUrlAuthorize = $azureUrlAuthorize;
        $this->azureUrlAccessToken = $azureUrlAccessToken;
        $this->azureUrlResourceOwnerDetails = $azureUrlResourceOwnerDetails;
        $this->tenant = $tenant;
    }

    public function saveUser(array $userData, string $accessToken): User
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy([
            'email' => $userData['email']
        ]);

        if (!$user) {
            $user = new User();
            $user->setEmail($userData['email']);
        }

        $user->setRoles($userData['roles']);
        $user->setFirstname($userData['firstname'] ?? '');
        $user->setLastname($userData['lastname'] ?? '');
        $user->setLastLogin(new \DateTime());

        if (method_exists($user, 'setAccessToken')) {
            $user->setAccessToken($accessToken);
        }

        /*if (method_exists($user, 'getDepartments') && method_exists($user, 'addDepartment')) {
            $departmentRepository = $this->entityManager->getRepository(Department::class);
            $user->getDepartments()->clear();

            foreach ($userData['roles'] as $role) {
                $departmentName = strtolower(str_replace('ROLE_', '', $role));
                $department = $departmentRepository->findOneBy([
                    'name' => $this->codeSite . 'intra-' . $departmentName
                ]);

                if ($department) {
                    $user->addDepartment($department);
                }
            }
        }*/

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    public function fetchAccessToken(string $authorizationCode): string
    {
        try {
            $provider = new Microsoft([
                'clientId' => $this->azureClientId,
                'clientSecret' => $this->azureClientSecret,
                'redirectUri' => $this->azureRedirectUri,
                'urlAuthorize' => $this->azureUrlAuthorize,
                'urlAccessToken' => $this->azureUrlAccessToken,
                'urlResourceOwnerDetails' => $this->azureUrlResourceOwnerDetails,
                'tenant' => $this->tenant,
            ]);



            $accessToken = $provider->getAccessToken('authorization_code', [
                'code' => $authorizationCode,
            ]);

            return $accessToken->getToken();
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de la récupération du token d\'accès : ' . $e->getMessage());
        }
    }

    public function fetchUserRoles(string $accessToken): array
    {
        $groupMap = [
            'GS_INTRA_ACCOUNTING'  => 'ROLE_ACCOUNTING',
            'GS_INTRA_ADV'         => 'ROLE_ADV',
            'GS_INTRA_CONTROLLING' => 'ROLE_CONTROLLING',
            'GS_INTRA_IT'          => 'ROLE_IT',
            'GS_INTRA_LOGISTIC'    => 'ROLE_LOGISTIC',
            'GS_INTRA_MANAGEMENT'  => 'ROLE_MANAGEMENT',
            'GS_INTRA_MARKETING'   => 'ROLE_MARKETING',
            'GS_INTRA_MODETIQEXP'  => 'ROLE_MODETIQEXP',
            'GS_INTRA_PURCHASING'  => 'ROLE_PURCHASING',
            'GS_INTRA_SALES'       => 'ROLE_SALES',
            'GS_INTRA_SAV'         => 'ROLE_SAV',
        ];

        try {
            $client = new Client();

            $response = $client->get('https://graph.microsoft.com/v1.0/me/memberOf', [
                'headers' => ['Authorization' => 'Bearer ' . $accessToken],
            ]);

            $data = json_decode($response->getBody(), true);
            $roles = [];

            foreach ($data['value'] ?? [] as $group) {
                $groupName = $group['displayName'] ?? null;
                if ($groupName && isset($groupMap[$groupName])) {
                    $roles[] = $groupMap[$groupName];
                }
                if ($groupName === 'GS_INTRA_IT') {
                    $roles[] = 'ROLE_ADMIN';
                }
            }

            return $roles ?: ['ROLE_USER'];
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de la récupération des rôles : ' . $e->getMessage());
        }
    }
}
