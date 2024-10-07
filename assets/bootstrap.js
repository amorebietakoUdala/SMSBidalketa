import { startStimulusApp } from '@symfony/stimulus-bridge';
import { datetimepicker } from '@amorebietakoudala/stimulus-controller-bundle/src/datetimepicker_controller';
//import { table } from '@amorebietakoudala/stimulus-controller-bundle/src/table_controller';

// Registers Stimulus controllers from controllers.json and in the controllers/ directory
export const app = startStimulusApp(require.context(
    '@symfony/stimulus-bridge/lazy-controller-loader!./controllers',
    true,
    /\.(j|t)sx?$/
));

// register any custom, 3rd party controllers here
// app.register('some_controller_name', SomeImportedController);
app.register('datetimepicker', datetimepicker );
//app.register('table', table );
//app.register('select2', select2 );