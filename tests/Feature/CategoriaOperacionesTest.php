<?php

namespace Tests\Feature;

use App\Models\Categoria;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class CategoriaOperacionesTest extends TestCase
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
     * Test de actualización exitosa de una categoría.
     */
    public function test_puede_actualizar_una_categoria_exitosamente(): void
    {
        // Crear una categoría
        $categoria = Categoria::create([
            'nombre' => 'Categoría Original',
            'descripcion' => 'Descripción original'
        ]);

        // Datos para actualizar
        $datosActualizados = [
            'nombre' => 'Categoría Actualizada',
            'descripcion' => 'Descripción actualizada'
        ];

        // Realizar la petición de actualización
        $response = $this->put(route('categoria.update', $categoria->id), $datosActualizados);

        // Verificar redirección
        $response->assertRedirect(route('categoria.index'));

        // Verificar que la categoría fue actualizada
        $this->assertDatabaseHas('categorias', [
            'id' => $categoria->id,
            'nombre' => 'Categoría Actualizada',
            'descripcion' => 'Descripción actualizada'
        ]);

        // Verificar que la original no existe
        $this->assertDatabaseMissing('categorias', [
            'nombre' => 'Categoría Original',
            'descripcion' => 'Descripción original'
        ]);
    }

    /**
     * Test que verifica que no se puede actualizar con un nombre duplicado.
     */
    public function test_no_puede_actualizar_categoria_con_nombre_duplicado(): void
    {
        // Crear dos categorías
        $categoria1 = Categoria::create([
            'nombre' => 'Categoría Uno',
            'descripcion' => 'Primera categoría'
        ]);

        $categoria2 = Categoria::create([
            'nombre' => 'Categoría Dos',
            'descripcion' => 'Segunda categoría'
        ]);

        // Intentar actualizar categoria2 con el nombre de categoria1
        $datosActualizados = [
            'nombre' => 'Categoría Uno',
            'descripcion' => 'Intento de duplicado'
        ];

        $response = $this->put(route('categoria.update', $categoria2->id), $datosActualizados);

        // Verificar que hay errores de validación
        $response->assertSessionHasErrors('nombre');

        // Verificar que la categoría 2 no fue actualizada
        $this->assertDatabaseHas('categorias', [
            'id' => $categoria2->id,
            'nombre' => 'Categoría Dos'
        ]);
    }


    /**
     * Test que verifica la eliminación de una categoría.
     */
    public function test_puede_eliminar_una_categoria(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Categoría a Eliminar',
            'descripcion' => 'Esta será eliminada'
        ]);

        $categoriaId = $categoria->id;

        // Eliminar la categoría
        $response = $this->delete(route('categoria.destroy', $categoriaId));

        // Verificar redirección
        $response->assertRedirect(route('categoria.index'));

        // Verificar que fue soft deleted
        $this->assertSoftDeleted('categorias', [
            'id' => $categoriaId
        ]);
    }

    /**
     * Test que intenta eliminar una categoría inexistente.
     */
    public function test_no_puede_eliminar_categoria_inexistente(): void
    {
        $response = $this->delete(route('categoria.destroy', 99999));

        $response->assertRedirect(route('categoria.index'));
    }

    /**
     * Test que intenta actualizar una categoría inexistente.
     */
    public function test_no_puede_actualizar_categoria_inexistente(): void
    {
        $datosActualizados = [
            'nombre' => 'Nombre Nuevo',
            'descripcion' => 'Descripción nueva'
        ];

        $response = $this->put(route('categoria.update', 99999), $datosActualizados);

        $response->assertRedirect(route('categoria.index'));
    }

    /**
     * Test de la vista index de categorías.
     */
    public function test_puede_ver_listado_de_categorias(): void
    {
        // Crear algunas categorías
        Categoria::create(['nombre' => 'Cat 1', 'descripcion' => 'Desc 1']);
        Categoria::create(['nombre' => 'Cat 2', 'descripcion' => 'Desc 2']);
        Categoria::create(['nombre' => 'Cat 3', 'descripcion' => 'Desc 3']);

        $response = $this->get(route('categoria.index'));

        $response->assertStatus(200);
        $response->assertViewIs('categoria.categoria-table');
        $response->assertViewHas('categorias');

        // Verificar que las categorías están en la vista
        $response->assertSee('Cat 1');
        $response->assertSee('Cat 2');
        $response->assertSee('Cat 3');
    }

    /**
     * Test de carga del formulario de upload.
     */
    public function test_puede_cargar_formulario_de_upload(): void
    {
        $response = $this->get(route('categoria.formUploadFile'));

        $response->assertStatus(200);
        $response->assertViewIs('categoria.categoria-upload');
        $response->assertViewHas('categorias');
    }

    /**
     * Test de validación del archivo de upload.
     */
    public function test_validacion_de_archivo_requerido_en_upload(): void
    {
        $response = $this->post(route('categoria.uploadFile'), []);

        $response->assertSessionHasErrors('archivo');
    }

    /**
     * Test de validación del tipo de archivo en upload.
     */
    public function test_validacion_de_tipo_de_archivo_en_upload(): void
    {
        // Intentar subir un archivo que no es Excel
        $archivoInvalido = UploadedFile::fake()->create('documento.txt', 100);

        $response = $this->post(route('categoria.uploadFile'), [
            'archivo' => $archivoInvalido
        ]);

        $response->assertSessionHasErrors('archivo');
    }

    /**
     * Test que verifica la relación con productos.
     */
    public function test_categoria_tiene_relacion_con_productos(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Categoría con Relación',
            'descripcion' => 'Test de relaciones'
        ]);

        // Verificar que la relación existe
        $this->assertInstanceOf(
            \Illuminate\Database\Eloquent\Relations\HasMany::class,
            $categoria->productos()
        );
    }

    /**
     * Test que verifica los campos fillable del modelo.
     */
    public function test_modelo_tiene_campos_fillable_correctos(): void
    {
        $categoria = new Categoria();

        $fillable = $categoria->getFillable();

        $this->assertContains('nombre', $fillable);
        $this->assertContains('descripcion', $fillable);
    }

    /**
     * Test que verifica el uso de SoftDeletes en el modelo.
     */
    public function test_modelo_usa_soft_deletes(): void
    {
        $categoria = new Categoria();

        // Verificar que el trait SoftDeletes está siendo usado
        $traits = class_uses($categoria);

        $this->assertContains(
            \Illuminate\Database\Eloquent\SoftDeletes::class,
            $traits
        );
    }

    /**
     * Test que verifica timestamps en actualización.
     */
    public function test_updated_at_cambia_al_actualizar_categoria(): void
    {
        $categoria = Categoria::create([
            'nombre' => 'Categoría Test',
            'descripcion' => 'Test'
        ]);

        $createdAt = $categoria->created_at;
        $updatedAtOriginal = $categoria->updated_at;

        // Esperar un segundo para asegurar cambio en timestamp
        sleep(1);

        // Actualizar la categoría
        $categoria->update(['nombre' => 'Categoría Actualizada']);

        $categoria->refresh();

        $this->assertEquals($createdAt, $categoria->created_at);
        $this->assertNotEquals($updatedAtOriginal, $categoria->updated_at);
    }
}
