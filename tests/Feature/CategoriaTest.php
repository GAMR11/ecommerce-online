<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoriaTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Usuario autenticado para los tests.
     */
    protected $user;

    /**
     * Setup que se ejecuta antes de cada test.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Crear y autenticar un usuario para todos los tests
        $this->user = User::factory()->create();
        $this->actingAs($this->user);
    }

    /**
     * Test que verifica la creación exitosa de una categoría.
     */
    public function test_puede_crear_una_categoria_exitosamente(): void
    {
        // Datos de prueba
        $datos = [
            'nombre' => 'Electrodomésticos',
            'descripcion' => 'Categoría para electrodomésticos del hogar'
        ];

        // Realizar la petición POST
        $response = $this->post(route('categoria.store'), $datos);

        // Verificar que redirige correctamente
        $response->assertRedirect(route('categoria.index'));

        // Verificar que la categoría fue creada en la base de datos
        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Electrodomésticos',
            'descripcion' => 'Categoría para electrodomésticos del hogar'
        ]);
    }

    /**
     * Test que verifica la creación de una categoría sin descripción.
     */
    public function test_puede_crear_una_categoria_sin_descripcion(): void
    {
        $datos = [
            'nombre' => 'Tecnología',
            'descripcion' => null
        ];

        $response = $this->post(route('categoria.store'), $datos);

        $response->assertRedirect(route('categoria.index'));

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Tecnología',
            'descripcion' => null
        ]);
    }

    /**
     * Test que verifica que el nombre es requerido.
     */
    public function test_nombre_es_requerido_para_crear_categoria(): void
    {
        $datos = [
            'nombre' => '',
            'descripcion' => 'Descripción de prueba'
        ];

        $response = $this->post(route('categoria.store'), $datos);

        // Verificar que hay errores de validación
        $response->assertSessionHasErrors('nombre');

        // Verificar que no se creó ninguna categoría
        $this->assertDatabaseMissing('categorias', [
            'descripcion' => 'Descripción de prueba'
        ]);
    }

    /**
     * Test que verifica que el nombre debe ser único.
     */
    public function test_nombre_debe_ser_unico(): void
    {
        // Crear una categoría primero
        Categoria::create([
            'nombre' => 'Muebles',
            'descripcion' => 'Categoría de muebles'
        ]);

        // Intentar crear otra con el mismo nombre
        $datos = [
            'nombre' => 'Muebles',
            'descripcion' => 'Otra descripción'
        ];

        $response = $this->post(route('categoria.store'), $datos);

        // Verificar que hay errores de validación
        $response->assertSessionHasErrors('nombre');

        // Verificar que solo existe una categoría con ese nombre
        $this->assertEquals(1, Categoria::where('nombre', 'Muebles')->count());
    }

    /**
     * Test que verifica la creación de múltiples categorías.
     */
    public function test_puede_crear_multiples_categorias(): void
    {
        $categorias = [
            ['nombre' => 'Deportes', 'descripcion' => 'Artículos deportivos'],
            ['nombre' => 'Juguetes', 'descripcion' => 'Juguetes para niños'],
            ['nombre' => 'Libros', 'descripcion' => 'Libros y revistas']
        ];

        foreach ($categorias as $categoria) {
            $response = $this->post(route('categoria.store'), $categoria);
            $response->assertRedirect(route('categoria.index'));
        }

        // Verificar que todas las categorías fueron creadas
        $this->assertEquals(3, Categoria::count());

        foreach ($categorias as $categoria) {
            $this->assertDatabaseHas('categorias', $categoria);
        }
    }

    /**
     * Test que verifica que el nombre tiene un límite de caracteres adecuado.
     */
    public function test_nombre_no_debe_exceder_limite_de_caracteres(): void
    {
        $datos = [
            'nombre' => str_repeat('a', 256), // Excede el límite de un VARCHAR(255)
            'descripcion' => 'Descripción válida'
        ];

        $response = $this->post(route('categoria.store'), $datos);

        // Debería haber errores de validación si hay una regla max
        $response->assertSessionHasErrors('nombre');
    }

    /**
     * Test que verifica la creación con descripción larga.
     */
    public function test_puede_crear_categoria_con_descripcion_larga(): void
    {
        $descripcionLarga = str_repeat('Esta es una descripción muy larga. ', 50);

        $datos = [
            'nombre' => 'Categoría con descripción larga',
            'descripcion' => $descripcionLarga
        ];

        $response = $this->post(route('categoria.store'), $datos);

        $response->assertRedirect(route('categoria.index'));

        // Verificar que la categoría fue creada (solo verificamos el nombre)
        $categoria = Categoria::where('nombre', 'Categoría con descripción larga')->first();
        $this->assertNotNull($categoria);
        $this->assertNotEmpty($categoria->descripcion);
        $this->assertStringContainsString('Esta es una descripción muy larga', $categoria->descripcion);
    }

    /**
     * Test que verifica que las categorías usan soft deletes.
     */
    public function test_categoria_usa_soft_deletes(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Categoría temporal',
            'descripcion' => 'Esta será eliminada'
        ]);

        $categoriaId = $categoria->id;

        // Eliminar la categoría
        $categoria->delete();

        // Verificar que la categoría fue soft deleted
        $this->assertSoftDeleted('categorias', [
            'id' => $categoriaId,
            'nombre' => 'Categoría temporal'
        ]);

        // Verificar que aún existe en la base de datos con deleted_at
        $this->assertDatabaseHas('categorias', [
            'id' => $categoriaId,
            'nombre' => 'Categoría temporal'
        ]);

        // Verificar que no aparece en consultas normales
        $this->assertNull(Categoria::find($categoriaId));

        // Verificar que aparece con withTrashed
        $this->assertNotNull(Categoria::withTrashed()->find($categoriaId));
    }

    /**
     * Test que verifica la creación con caracteres especiales en el nombre.
     */
    public function test_puede_crear_categoria_con_caracteres_especiales(): void
    {
        $datos = [
            'nombre' => 'Ropa & Accesorios',
            'descripcion' => 'Categoría con caracteres especiales: áéíóú ñ'
        ];

        $response = $this->post(route('categoria.store'), $datos);

        $response->assertRedirect(route('categoria.index'));

        $this->assertDatabaseHas('categorias', [
            'nombre' => 'Ropa & Accesorios'
        ]);
    }

    /**
     * Test que verifica los timestamps se crean correctamente.
     */
    public function test_categoria_tiene_timestamps(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Categoría con timestamps',
            'descripcion' => 'Test de timestamps'
        ]);

        $this->assertNotNull($categoria->created_at);
        $this->assertNotNull($categoria->updated_at);
        $this->assertEquals($categoria->created_at, $categoria->updated_at);
    }
}
