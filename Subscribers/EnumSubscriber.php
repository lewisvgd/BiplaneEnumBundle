<?php

namespace Biplane\EnumBundle\Subscribers;


use Doctrine\ORM\Events;
use Doctrine\Common\EventSubscriber;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\Common\Annotations\Reader;
use \Doctrine\ORM\EntityManager;
use \ReflectionClass;

/**
 * Doctrine event subscriber which converts to/from enums
 */
class EnumSubscriber implements EventSubscriber {

    const ANN_NAME = 'Biplane\EnumBundle\Configuration\Enum';

    /**
     * Annotation reader
     * @var Doctrine\Common\Annotations\Reader
     */
    private $annReader;
    
    public function __construct(Reader $annReader) {
        $this->annReader = $annReader;
    }

    /**
     * Listen a prePersist lifecycle event. Checking and convert entities
     * which have @Enum annotation
     * @param LifecycleEventArgs $args 
     */
    public function prePersist(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $this->processFields($entity, false);
    }

    /**
     * Listen a preUpdate lifecycle event. Checking and convert entities fields
     * which have @enum annotation. U
     * @param LifecycleEventArgs $args 
     */
    public function preUpdate(PreUpdateEventArgs $args) {
        $reflectionClass = new ReflectionClass($args->getEntity());
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $refProperty) {
            if ($this->annReader->getPropertyAnnotation($refProperty, self::ANN_NAME)) {
                $propName = $refProperty->getName();
                $args->setNewValue($propName, $refProperty->getValue()->getValue());
            }
        }
    }
    
    /**
     * Listen a postLoad lifecycle event. Checking and convert entities
     * which have @Enum annotations
     * @param LifecycleEventArgs $args
     */
    public function postLoad(LifecycleEventArgs $args) {
        $entity = $args->getEntity();
        $this->processFields($entity, true);
    }

    /**
     * Realization of EventSubscriber interface method.
     * @return Array Return all events which this subscriber is listening
     */
    public function getSubscribedEvents() {
        return array(
            Events::prePersist,
            Events::preUpdate,
            Events::postLoad,
        );
    }

    public static function capitalize($word) {
        if(is_array($word)) {
            $word = $word[0];
        }

        return str_replace(' ', '', ucwords(str_replace(array('-', '_'), ' ', $word)));
    }

    /**
     * Process (toString / Enum::create) entities fields
     * @param Obj $entity Some doctrine entity
     * @param Boolean $isValue If true - convert to entity, false - convert to string
     */
    private function processFields($entity, $isValue) {

        $reflectionClass = new ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $refProperty) {
            if ($ann = ($this->annReader->getPropertyAnnotation($refProperty, self::ANN_NAME))) {
                // we have annotation and if it decrypt operation, we must avoid duble decryption
                $propName = $refProperty->getName();
                $cls = $ann->value;
                if ($refProperty->isPublic()) {
                    if (!$isValue) {
                        $entity->$propName = $refProperty->getValue()->getValue();
                    }else {
                        $entity->$propName = $cls::create($refProperty->getValue());
                    }
                } else {
                    $methodName = self::capitalize($propName);
                    if ($reflectionClass->hasMethod($getter = 'get' . $methodName) && $reflectionClass->hasMethod($setter = 'set' . $methodName)) {
                        if (!$isValue) {
                            $entity->$setter($entity->{$getter}()->getValue());
                        }else{
                            $entity->$setter($cls::create($entity->{$getter}())->getValue());
                        }
                    } else {
                        throw new \RuntimeException(sprintf("Property %s isn't public and doesn't has getter/setter"));
                    }
                }
            }
        }
        
        return true;
    }
    
}