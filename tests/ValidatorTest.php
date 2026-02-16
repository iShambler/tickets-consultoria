<?php
/**
 * Test de validación
 * Pruebas unitarias básicas para la clase Validator
 */

require_once __DIR__ . '/../app/autoload.php';

use App\Utils\Validator;

class ValidatorTest
{
    public function testRequiredField()
    {
        // Test campo requerido vacío
        $validator = new Validator(['nombre' => '']);
        $validator->required('nombre');
        assert($validator->fails() === true, 'Campo requerido vacío debe fallar');
        
        // Test campo requerido con valor
        $validator = new Validator(['nombre' => 'Juan']);
        $validator->required('nombre');
        assert($validator->passes() === true, 'Campo requerido con valor debe pasar');
        
        echo "✓ Test required field pasado\n";
    }
    
    public function testEmailValidation()
    {
        // Test email inválido
        $validator = new Validator(['email' => 'no-es-email']);
        $validator->email('email');
        assert($validator->fails() === true, 'Email inválido debe fallar');
        
        // Test email válido
        $validator = new Validator(['email' => 'usuario@ejemplo.com']);
        $validator->email('email');
        assert($validator->passes() === true, 'Email válido debe pasar');
        
        echo "✓ Test email validation pasado\n";
    }
    
    public function testMinLength()
    {
        // Test longitud menor que mínimo
        $validator = new Validator(['password' => '123']);
        $validator->min('password', 6);
        assert($validator->fails() === true, 'Longitud menor que mínimo debe fallar');
        
        // Test longitud mayor que mínimo
        $validator = new Validator(['password' => '123456']);
        $validator->min('password', 6);
        assert($validator->passes() === true, 'Longitud mayor que mínimo debe pasar');
        
        echo "✓ Test min length pasado\n";
    }
    
    public function testMaxLength()
    {
        // Test longitud mayor que máximo
        $validator = new Validator(['nombre' => str_repeat('a', 101)]);
        $validator->max('nombre', 100);
        assert($validator->fails() === true, 'Longitud mayor que máximo debe fallar');
        
        // Test longitud menor que máximo
        $validator = new Validator(['nombre' => 'Juan']);
        $validator->max('nombre', 100);
        assert($validator->passes() === true, 'Longitud menor que máximo debe pasar');
        
        echo "✓ Test max length pasado\n";
    }
    
    public function testSanitize()
    {
        $data = [
            'nombre' => '<script>alert("xss")</script>Juan',
            'email' => 'test@example.com  '
        ];
        
        $cleaned = Validator::sanitize($data);
        
        assert(!str_contains($cleaned['nombre'], '<script>'), 'Debe eliminar scripts');
        assert($cleaned['email'] === 'test@example.com', 'Debe eliminar espacios');
        
        echo "✓ Test sanitize pasado\n";
    }
    
    public function runAllTests()
    {
        echo "Ejecutando tests de Validator...\n\n";
        
        $this->testRequiredField();
        $this->testEmailValidation();
        $this->testMinLength();
        $this->testMaxLength();
        $this->testSanitize();
        
        echo "\n✓ Todos los tests pasaron exitosamente\n";
    }
}

// Ejecutar tests si se llama directamente
if (basename(__FILE__) === basename($_SERVER['PHP_SELF'])) {
    $test = new ValidatorTest();
    $test->runAllTests();
}
