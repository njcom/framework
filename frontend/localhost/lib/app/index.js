define("localhost/lib/app/index", ["require", "exports", "localhost/lib/base/app"], function (require, exports, app_1) {
    "use strict";
    Object.defineProperty(exports, "__esModule", { value: true });
    exports.App = void 0;
    class App extends app_1.App {
        bindEventHandlers() {
            this.bindMainMenuHandlers();
        }
        bindMainMenuHandlers() {
            const uriPath = window.location.pathname;
            $('#main-menu a').each(function () {
                const $a = $(this);
                let linkUri = $a.attr('href');
                if (!linkUri) {
                    return;
                }
                if (linkUri.substr(0, 1) !== '/') {
                    return;
                }
                let offset = linkUri.indexOf('?');
                if (offset >= 0) {
                    linkUri = linkUri.substr(0, offset);
                }
                offset = linkUri.indexOf('#');
                if (offset >= 0) {
                    linkUri = linkUri.substr(0, offset);
                }
                if (linkUri === uriPath) {
                    $a.addClass('active');
                    $a.closest('.dropdown').find('.nav-link:first').addClass('active');
                }
            });
        }
    }
    exports.App = App;
    window.app = new App();
});
//# sourceMappingURL=data:application/json;base64,eyJ2ZXJzaW9uIjozLCJmaWxlIjoiaW5kZXguanMiLCJzb3VyY2VSb290IjoiIiwic291cmNlcyI6WyJpbmRleC50cyJdLCJuYW1lcyI6W10sIm1hcHBpbmdzIjoiOzs7O0lBSUEsTUFBYSxHQUFJLFNBQVEsU0FBTztRQUNsQixpQkFBaUI7WUFDdkIsSUFBSSxDQUFDLG9CQUFvQixFQUFFLENBQUM7UUFDaEMsQ0FBQztRQUVPLG9CQUFvQjtZQUN4QixNQUFNLE9BQU8sR0FBRyxNQUFNLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQztZQUN6QyxDQUFDLENBQUMsY0FBYyxDQUFDLENBQUMsSUFBSSxDQUFDO2dCQUNuQixNQUFNLEVBQUUsR0FBRyxDQUFDLENBQUMsSUFBSSxDQUFDLENBQUM7Z0JBQ25CLElBQUksT0FBTyxHQUFHLEVBQUUsQ0FBQyxJQUFJLENBQUMsTUFBTSxDQUFDLENBQUM7Z0JBQzlCLElBQUksQ0FBQyxPQUFPLEVBQUU7b0JBQ1YsT0FBTztpQkFDVjtnQkFDRCxJQUFJLE9BQU8sQ0FBQyxNQUFNLENBQUMsQ0FBQyxFQUFFLENBQUMsQ0FBQyxLQUFLLEdBQUcsRUFBRTtvQkFDOUIsT0FBTztpQkFDVjtnQkFDRCxJQUFJLE1BQU0sR0FBRyxPQUFPLENBQUMsT0FBTyxDQUFDLEdBQUcsQ0FBQyxDQUFDO2dCQUNsQyxJQUFJLE1BQU0sSUFBSSxDQUFDLEVBQUU7b0JBQ2IsT0FBTyxHQUFHLE9BQU8sQ0FBQyxNQUFNLENBQUMsQ0FBQyxFQUFFLE1BQU0sQ0FBQyxDQUFDO2lCQUN2QztnQkFDRCxNQUFNLEdBQUcsT0FBTyxDQUFDLE9BQU8sQ0FBQyxHQUFHLENBQUMsQ0FBQztnQkFDOUIsSUFBSSxNQUFNLElBQUksQ0FBQyxFQUFFO29CQUNiLE9BQU8sR0FBRyxPQUFPLENBQUMsTUFBTSxDQUFDLENBQUMsRUFBRSxNQUFNLENBQUMsQ0FBQztpQkFDdkM7Z0JBQ0QsSUFBSSxPQUFPLEtBQUssT0FBTyxFQUFFO29CQUNyQixFQUFFLENBQUMsUUFBUSxDQUFDLFFBQVEsQ0FBQyxDQUFBO29CQUNyQixFQUFFLENBQUMsT0FBTyxDQUFDLFdBQVcsQ0FBQyxDQUFDLElBQUksQ0FBQyxpQkFBaUIsQ0FBQyxDQUFDLFFBQVEsQ0FBQyxRQUFRLENBQUMsQ0FBQztpQkFDdEU7WUFDTCxDQUFDLENBQUMsQ0FBQztRQUNQLENBQUM7S0FDSjtJQTlCRCxrQkE4QkM7SUFFRCxNQUFNLENBQUMsR0FBRyxHQUFHLElBQUksR0FBRyxFQUFFLENBQUMifQ==