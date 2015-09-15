<?php
/**
 * Entity class holding user credentials for the Byte environment.
 *
 * @package Len\Environment\Credentials
 */

namespace Len\Environment\Credentials;

use \Symfony\Component\Console\Helper\QuestionHelper;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Entity class holding user credentials for the Byte environment.
 */
final class ByteCredentials
{
    /**
     * The user name.
     *
     * @var string $_user
     */
    protected $_user;

    /**
     * The password.
     *
     * @var string $_password
     */
    protected $_password;

    /**
     * ByteCredentials constructor.
     *
     * @param string $user
     * @param string $password
     */
    public function __construct($user, $password)
    {
        $this->setUser($user);
        $this->setPassword($password);
    }

    /**
     * Setter for the _user property.
     *
     * @param string $user
     * @return ByteCredentials
     * @throws \InvalidArgumentException when $user is not of type string.
     */
    private function setUser($user)
    {
        if (!is_string($user)) {
            throw new \InvalidArgumentException(
                'Invalid user supplied: ' . var_export($user, true)
            );
        }

        $this->_user = $user;

        return $this;
    }

    /**
     * Setter for the _password property.
     *
     * @param string $password
     * @return ByteCredentials
     * @throws \InvalidArgumentException when $password is not of type string.
     */
    private function setPassword($password)
    {
        if (!is_string($password)) {
            throw new \InvalidArgumentException(
                'Invalid password supplied: ' . var_export($password, true)
            );
        }

        $this->_password = $password;

        return $this;
    }

    /**
     * Getter for the _user property.
     *
     * @return string
     * @throws \LogicException when property _user is not set.
     */
    public function getUser()
    {
        if (!isset($this->_user)) {
            throw new \LogicException('Missing property _user');
        }
        return $this->_user;
    }

    /**
     * Getter for the _password property.
     *
     * @return string
     * @throws \LogicException when property _password is not set.
     */
    public function getPassword()
    {
        if (!isset($this->_password)) {
            throw new \LogicException('Missing property _password');
        }
        return $this->_password;
    }

    /**
     * Create Byte credentials from the supplied question helper.
     *
     * @param QuestionHelper $helper
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return static
     */
    public static function fromQuestionHelper(
        QuestionHelper $helper,
        InputInterface $input,
        OutputInterface $output
    )
    {
        $userQuestion = new Question('Byte user: ');

        $passwordQuestion = new Question('Byte password: ');
        $passwordQuestion->setHidden(true);
        $passwordQuestion->setHiddenFallback(false);

        return new static(
            $helper->ask($input, $output, $userQuestion),
            $helper->ask($input, $output, $passwordQuestion)
        );
    }

    /**
     * Create Byte credentials from the supplied authentication file.
     *
     * @param string $authFile
     * @return static
     * @throws \InvalidArgumentException when $authFile is not a string
     * @throws \RuntimeException when $authFile is not readable
     * @throws \RuntimeException when the structure inside $authFile is
     *   corrupted.
     */
    public static function fromAuthFile($authFile)
    {
        if (!is_string($authFile)) {
            throw new \InvalidArgumentException(
                'Authentication file should be a string: ' . gettype($authFile)
            );
        }

        $file = realpath($authFile);

        if (!is_readable($file)) {
            throw new \RuntimeException("Cannot open auth file: {$file}");
        }

        $json = file_get_contents($file);
        $credentials = json_decode($json);

        if (empty($credentials)
            || !is_object($credentials)
            || !property_exists($credentials, 'username')
            || !property_exists($credentials, 'password')
        ) {
            throw new \RuntimeException(
                "Authentication file is corrupted: {$file}"
            );
        }

        return new static($credentials->username, $credentials->password);
    }
}
