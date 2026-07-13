/**
 * Retrieves the translation of text.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-i18n/
 */
import { __ } from '@wordpress/i18n';

/**
 * React hook that is used to mark the block wrapper element.
 * It provides all the necessary props like the class name.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/packages/packages-block-editor/#useblockprops
 */
import { InspectorControls, useBlockProps } from '@wordpress/block-editor';

import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';

/**
 * Lets webpack process CSS, SASS or SCSS files referenced in JavaScript files.
 * Those files can contain any CSS code that gets applied to the editor.
 *
 * @see https://www.npmjs.com/package/@wordpress/scripts#using-css
 */
import './editor.scss';

/**
 * The edit function describes the structure of your block in the context of the
 * editor. This represents what the editor will render when the block is used.
 *
 * @see https://developer.wordpress.org/block-editor/reference-guides/block-api/block-edit-save/#edit
 *
 * @return {Element} Element to render.
 */

export default function Edit({ attributes, setAttributes } ) {
	const { id  } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Arguments', 'filter-everything' ) }>
					<TextControl
						__next40pxDefaultSize
						help={ __(
							'id – determines which Filter Set to open. Optional parameter. By default, the button will open Filter Set with highest priority if there are several of them on the page.',
							'filter-everything'
						) }
						label={ __(
							'id',
							'filter-everything'
						) }
						value={ id || '' }
						onChange={ ( value ) =>
							setAttributes( { id: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>
				{ __( 'Filter Everything - Filter button', 'filter-everything' ) }
			</p>
		</>
	);
}
