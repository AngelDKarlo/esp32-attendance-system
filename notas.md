Alumno:
-Resumen de Asistencia Personal: Consulta detallada materia por materia.
-Cálculo Real de Asistencia: Muestra total de clases impartidas, asistencias, retardos, faltas y justificantes.
-Porcentaje Dinámico: Porcentaje de asistencia calculado sobre las clases realmente impartidas por el docente.
-Alertas de Desempeño: Identificación visual de materias con riesgo por acumulación de faltas.

Docente:
-Gestión por Sesión: Selección de clase por materia, grupo y fecha.
-Lista de Asistencia Dinámica.
-Edición y Corrección Manual: Posibilidad de ajustar manualmente cualquier registro de la clase en tiempo real.
-Firma Digital / Bypass: Permite autorizar la asistencia a alumnos que carezcan de cuenta o bot de Telegram mediante su tarjeta RFID docente.

Administrador:
- Gestión de Usuarios:
-- Alta, edición, baja lógica y restablecimiento de contraseñas.
-- Captura de Matrícula, Rol (Alumno, Docente, Administrativo), Correo, ID de Telegram y UID de Tarjeta RFID.

-Gestión de Clases y Horarios:
--Configuración de Materia, Carrera, Grupo, Semestre, Aula, Día y Rango de Horas.
--Detección de Empalmes: Validación automática para evitar que un docente tenga clases cruzadas a la misma hora.

-Asignación e Inscripciones:
--Asignación explícita de alumnos a materias.
--Inscripción Masiva: Botón para inscribir automáticamente a todo un grupo según su Carrera, Grupo y Semestre.

-Control de Cajas ESP32:
--Registro e identificación única de cada hardware.
--Asignación de Nombre, Aula física y Estado.
--Auditoría de conectividad (Última IP, fecha/hora de conexión y total de lecturas).

-Historial Global y Auditoría:
--Filtros avanzados por rango de fechas, materia, usuario o estado.
--Edición rápida  y eliminación con conservación de filtros activos.
--Exportación de Datos: Descarga de reportes en formato CSV.

COSAS A AGREGAR ACTUAL:
- Un apartado donde el alumno pueda justificar una inasistencia de un dia en especifico, que pueda escrivir la razon y adjuntaar una fotografia ya sea de su receta medica o algo que justufuqie la falta.

Porfesor:
- En los horarios de profesor esten sus grupos y la lista de alumnos de dicho dia. 
-justificar innacistencias, que haya algun apartado donde puedas aceptar innacistencias  o mas bien leerlas y tu decides si aceptar o no, en caso de no poder escriobir un comentario de porqe no lo hiciste,.
- en la lista de los alumnos debe haber un porsentaje de asistencias por alumno y estadisticas generales del mismo por persona, ya que depende de los criterios se puede reprobar a un alumno si tiene menos del 80%
-. poder decir que un dia ha sido inabil y que no cuente, eso lo decide el profesor. 

por la parte de el escaneo. tengo 2 ideas, una es que todas las clases sean por hora y secion especifica, y que el aparato empieze a marcar la lista cunado ha llegado la hora de iniciar la clase o que el profesor active la clase al pasar lista con su tarjeta, me parece mejorn la primera por cuaklqiier cosa de que el profesor falte, o una convinacion de ambas, por ejemplo, en un salon especifoico el aparato en especifico esta programado para pasar la asisrtencia en cierta hora, pero si el maestro tiene que cambiar de salonpueda pasar su tarjeta en el dispositivo de otra unidad(esto podemos dejarlo sin hacer de momento pero quiero tu opinion)

en caso de necesitar agrear algo a la base de datos solo hazlo.
