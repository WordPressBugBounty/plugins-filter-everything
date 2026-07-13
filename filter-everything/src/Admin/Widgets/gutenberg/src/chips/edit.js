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
	const { title, set_id, mobile  } = attributes;
	return (
		<>
			<InspectorControls>
				<PanelBody title={ __( 'Settings', 'filter-everything' ) }>
					<TextControl
						__next40pxDefaultSize
						label={ __(
							'Title',
							'filter-everything'
						) }
						value={ title || '' }
						onChange={ ( value ) =>
							setAttributes( { title: value } )
						}
					/>
					<TextControl
						__next40pxDefaultSize
						label={ __(
							'Show Chips only for Set with IDs:',
							'filter-everything'
						) }
						help={ __('e.g. 2745, 324', 'filter-everything')}
						value={ set_id || '' }
						onChange={ ( value ) =>
							setAttributes( { set_id: value } )
						}
					/>
					<ToggleControl
						checked={ !! mobile }
						label={ __(
							'Show on mobile',
							'filter-everything'
						) }
						onChange={ () =>
							setAttributes( {
								mobile: ! mobile,
							} )
						}
					/>
				</PanelBody>
			</InspectorControls>
			<p { ...useBlockProps() }>
				{ __( 'Filter Everything - Chips', 'filter-everything' ) }
			</p>
		</>
	);
}
